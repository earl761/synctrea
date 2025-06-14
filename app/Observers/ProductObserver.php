<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ConnectionPair;
use App\Models\ConnectionPairProduct;
use App\Services\SyncService;
use App\Services\SyncStatusManager;
use App\Services\Api\AmazonApiClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductObserver
{
    protected $relevantFields = [
        'name',
        'sku',
        'upc',
        'condition',
        'part_number',
        'cost_price',
        'retail_price',
        'stock_quantity',
        'weight'
    ];

    public function created(Product $product)
    {
        Log::info('New product created, syncing to connection pairs', [
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id
        ]);

        try {
            // Find all active connection pairs for this supplier
            $connectionPairs = ConnectionPair::where('supplier_id', $product->supplier_id)
                ->where('is_active', true)
                ->get();

            if ($connectionPairs->isEmpty()) {
                Log::info('No active connection pairs found for supplier', [
                    'supplier_id' => $product->supplier_id
                ]);
                return;
            }

            DB::beginTransaction();

            foreach ($connectionPairs as $connectionPair) {
                // Create connection pair product with all necessary fields
                $connectionPairProduct = ConnectionPairProduct::create([
                    'connection_pair_id' => $connectionPair->id,
                    'product_id' => $product->id,
                    'sku' => $connectionPair->sku_prefix . $product->sku,
                    'name' => $product->name,
                    'upc' => $product->upc,
                    'condition' => $product->condition,
                    'part_number' => $product->part_number,
                    'price' => $product->cost_price,
                    'fila_price' => $product->retail_price,
                    'stock' => $product->stock_quantity,
                    'weight' => $product->weight ?? 0,
                    'catalog_status' => ConnectionPairProduct::STATUS_DEFAULT,
                    'sync_status' => 'pending',
                    'price_override_type' => ConnectionPairProduct::PRICE_OVERRIDE_NONE
                ]);

                Log::info('Created connection pair product', [
                    'connection_pair_id' => $connectionPair->id,
                    'product_id' => $product->id,
                    'connection_pair_product_id' => $connectionPairProduct->id
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create connection pair products', [
                'product_id' => $product->id,
                'supplier_id' => $product->supplier_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }


    }

    public function updated(Product $product)
    {
        $syncService = app(SyncService::class);
        $changedFields = array_keys($product->getDirty());
        
        try {
            $syncService->syncProductToConnectionPairs($product, $changedFields);
        } catch (\Exception $e) {
            Log::error('Failed to sync product updates via SyncService', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleted(Product $product)
    {
        Log::info('Product deleted, cleaning up connection pair products', [
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id
        ]);

        $syncStatusManager = app(SyncStatusManager::class);

        try {
            DB::beginTransaction();

            // Get all connection pair products for this product
            $connectionPairProducts = ConnectionPairProduct::where('product_id', $product->id)->get();

            foreach ($connectionPairProducts as $connectionPairProduct) {
                // If the product is in catalog, mark it for deletion
                if ($connectionPairProduct->catalog_status === ConnectionPairProduct::STATUS_IN_CATALOG) {
                    $syncStatusManager->updateCatalogStatus(
                        $connectionPairProduct, 
                        SyncStatusManager::CATALOG_STATUS_PENDING_DELETION,
                        'Product deleted'
                    );
                    $syncStatusManager->markAsPending($connectionPairProduct, 'Product deleted - needs catalog removal');

                    Log::info('Marked connection pair product for deletion from catalog', [
                        'connection_pair_product_id' => $connectionPairProduct->id,
                        'product_id' => $product->id
                    ]);
                } else {
                    // If not in catalog, soft delete immediately
                    $connectionPairProduct->delete();

                    Log::info('Soft deleted connection pair product', [
                        'connection_pair_product_id' => $connectionPairProduct->id,
                        'product_id' => $product->id
                    ]);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cleanup connection pair products', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


}
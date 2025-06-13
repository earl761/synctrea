<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ConnectionPair;
use App\Models\ConnectionPairProduct;
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
        // Check if any relevant fields were changed
        $relevantFields = [
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

        $hasRelevantChanges = false;
        foreach ($relevantFields as $field) {
            if ($product->wasChanged($field)) {
                $hasRelevantChanges = true;
                break;
            }
        }

        if (!$hasRelevantChanges) {
            Log::info('No relevant fields changed, skipping sync', [
                'product_id' => $product->id
            ]);
            return;
        }

        Log::info('Product updated, syncing to connection pairs', [
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id,
            'changed_fields' => $product->getDirty()
        ]);

        try {
            // Get all active connection pair products for this product
            $connectionPairProducts = ConnectionPairProduct::where('product_id', $product->id)
                ->whereHas('connectionPair', function ($query) {
                    $query->where('is_active', true);
                })
                ->get();

            if ($connectionPairProducts->isEmpty()) {
                Log::info('No active connection pair products found for product', [
                    'product_id' => $product->id
                ]);
                return;
            }

            DB::beginTransaction();

            foreach ($connectionPairProducts as $connectionPairProduct) {
                $updates = [
                    'name' => $product->name,
                    'upc' => $product->upc,
                    'condition' => $product->condition,
                    'part_number' => $product->part_number,
                    'price' => $product->cost_price,
                    'fila_price' => $product->retail_price,
                    'stock' => $product->stock_quantity,
                    'weight' => $product->weight ?? 0,
                    'sync_status' => 'pending'
                ];

                // Only update SKU if product SKU changed (to preserve custom SKU prefixes)
                if ($product->wasChanged('sku')) {
                    $updates['sku'] = $connectionPairProduct->connectionPair->sku_prefix . $product->sku;
                }

                $connectionPairProduct->update($updates);

                Log::info('Updated connection pair product', [
                    'connection_pair_product_id' => $connectionPairProduct->id,
                    'product_id' => $product->id,
                    'updated_fields' => array_keys($updates)
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update connection pair products', [
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

        try {
            DB::beginTransaction();

            // Get all connection pair products for this product
            $connectionPairProducts = ConnectionPairProduct::where('product_id', $product->id)->get();

            foreach ($connectionPairProducts as $connectionPairProduct) {
                // If the product is in catalog, mark it for deletion
                if ($connectionPairProduct->catalog_status === ConnectionPairProduct::STATUS_IN_CATALOG) {
                    $connectionPairProduct->update([
                        'catalog_status' => 'pending_deletion',
                        'sync_status' => 'pending'
                    ]);

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
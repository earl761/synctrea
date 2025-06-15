<?php

namespace App\Services;

use App\Jobs\BatchSyncConnectionPairProductsJob;
use App\Jobs\SyncToAmazonJob;
use App\Jobs\SyncToPrestaShopJob;
use App\Models\Product;
use App\Models\ConnectionPairProduct;
use App\Models\ConnectionPair;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class SyncService
{
    /**
     * Critical fields that trigger sync when changed
     */
    private const CRITICAL_SYNC_FIELDS = [
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

    /**
     * Sync a product to all its connection pairs
     */
    public function syncProductToConnectionPairs(Product $product, array $changedFields = []): void
    {
        // Check if any critical fields changed
        if (!empty($changedFields) && !$this->hasCriticalFieldChanges($changedFields)) {
            Log::info('No critical fields changed, skipping sync', [
                'product_id' => $product->id,
                'changed_fields' => $changedFields
            ]);
            return;
        }

        Log::info('Syncing product to connection pairs', [
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id,
            'changed_fields' => $changedFields
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

            $this->batchUpdateConnectionPairProducts($connectionPairProducts, $product, $changedFields);

        } catch (\Exception $e) {
            Log::error('Failed to sync product to connection pairs', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Batch sync multiple products
     */
    public function batchSyncProducts(Collection $products): void
    {
        Log::info('Starting batch sync for products', [
            'product_count' => $products->count()
        ]);

        try {
            DB::beginTransaction();

            foreach ($products as $product) {
                $this->syncProductToConnectionPairs($product);
            }

            DB::commit();

            Log::info('Batch sync completed successfully', [
                'product_count' => $products->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch sync failed', [
                'product_count' => $products->count(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate sync conditions for a connection pair product
     */
    public function validateSyncConditions(ConnectionPairProduct $connectionPairProduct): bool
    {
        // Check if connection pair exists and is active
        $connectionPair = $connectionPairProduct->connectionPair;
        if (!$connectionPair || !$connectionPair->is_active) {
            Log::info('Connection pair not active, skipping sync', [
                'connection_pair_product_id' => $connectionPairProduct->id,
                'connection_pair_id' => $connectionPair?->id ?? 'null'
            ]);
            return false;
        }

        // Check if company exists and has active subscription
        $company = $connectionPair->company;
        if (!$company || !$company->isSubscriptionActive()) {
            Log::info('Company subscription not active, skipping sync', [
                'connection_pair_product_id' => $connectionPairProduct->id,
                'company_id' => $company?->id ?? 'null'
            ]);
            return false;
        }

        // Check if product exists
        if (!$connectionPairProduct->product) {
            Log::warning('Product not found for connection pair product', [
                'connection_pair_product_id' => $connectionPairProduct->id,
                'product_id' => $connectionPairProduct->product_id
            ]);
            return false;
        }

        return true;
    }

    /**
     * Dispatch sync job for a connection pair product
     */
    public function dispatchSyncJob(ConnectionPairProduct $product): void
    {
        $destination = $product->connectionPair->destination;
        
        if ($destination->type === 'woocomerce') {
            SyncToAmazonJob::dispatch($product);
        } elseif ($destination->type === 'prestashop') {
            SyncToPrestaShopJob::dispatch($product);
        }
    }

    /**
     * Retry failed syncs
     */
    public function retryFailedSyncs(?int $connectionPairId = null): int
    {
        $query = ConnectionPairProduct::where('sync_status', SyncStatusManager::SYNC_STATUS_FAILED);
        
        if ($connectionPairId) {
            $query->where('connection_pair_id', $connectionPairId);
        }
        
        $products = $query->get();
        
        foreach ($products as $product) {
            $this->dispatchSyncJob($product);
        }
        
        return $products->count();
    }

    /**
     * Perform batch sync
     */
    public function performBatchSync(?int $connectionPairId = null, int $chunkSize = 100): int
    {
        $query = ConnectionPairProduct::where('sync_status', SyncStatusManager::SYNC_STATUS_PENDING);
        
        if ($connectionPairId) {
            $query->where('connection_pair_id', $connectionPairId);
        }
        
        $totalCount = $query->count();
        
        if ($totalCount > 0) {
            BatchSyncConnectionPairProductsJob::dispatch($connectionPairId, $chunkSize);
        }
        
        return $totalCount;
    }

    /**
     * Check if any critical fields changed
     */
    private function hasCriticalFieldChanges(array $changedFields): bool
    {
        return !empty(array_intersect($changedFields, self::CRITICAL_SYNC_FIELDS));
    }

    /**
     * Batch update connection pair products
     */
    private function batchUpdateConnectionPairProducts(
        Collection $connectionPairProducts,
        Product $product,
        array $changedFields
    ): void {
        DB::beginTransaction();

        try {
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
                if (empty($changedFields) || in_array('sku', $changedFields)) {
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
            throw $e;
        }
    }
}
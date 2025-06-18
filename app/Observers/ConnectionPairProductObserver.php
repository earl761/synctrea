<?php

namespace App\Observers;

use App\Models\ConnectionPairProduct;
use App\Services\SyncService;
use App\Services\SyncStatusManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ConnectionPairProductObserver
{
    /**
     * Handle the ConnectionPairProduct "created" event.
     */
    public function created(ConnectionPairProduct $connectionPairProduct): void
    {
        $this->handleSyncTrigger($connectionPairProduct, 'created');
    }

    /**
     * Handle the ConnectionPairProduct "updated" event.
     */
    public function updated(ConnectionPairProduct $connectionPairProduct): void
    {
        // If last_synced_at was changed, don't trigger to avoid loops
        if ($connectionPairProduct->isDirty('last_synced_at')) {
            return;
        }

        // If sync_status was changed to completed, don't trigger
        if ($connectionPairProduct->isDirty('sync_status') && 
            $connectionPairProduct->sync_status === SyncStatusManager::STATUS_COMPLETED) {
            return;
        }

        // Check if catalog_status changed from 'in_catalog' to 'queued' for Amazon deletion
        if ($connectionPairProduct->isDirty('catalog_status') &&
            $connectionPairProduct->getOriginal('catalog_status') === ConnectionPairProduct::STATUS_IN_CATALOG &&
            $connectionPairProduct->catalog_status === ConnectionPairProduct::STATUS_QUEUED) {
            $this->handleAmazonDeletion($connectionPairProduct);
        }

        $this->handleSyncTrigger($connectionPairProduct, 'updated');
    }

    /**
     * Handle sync trigger for created/updated events
     */
    private function handleSyncTrigger(ConnectionPairProduct $connectionPairProduct, string $event): void
    {
        $syncService = app(SyncService::class);
        $syncStatusManager = app(SyncStatusManager::class);

        try {
            // Check if sync is needed
            if (!$syncStatusManager->needsSync($connectionPairProduct)) {
                Log::debug('Sync not needed for connection pair product', [
                    'connection_pair_product_id' => $connectionPairProduct->id,
                    'current_status' => $connectionPairProduct->sync_status,
                    'event' => $event
                ]);
                return;
            }

            // Check for recent sync attempts to prevent spam
            if ($syncStatusManager->hasRecentSyncAttempt($connectionPairProduct)) {
                Log::info('Recent sync attempt detected, skipping', [
                    'connection_pair_product_id' => $connectionPairProduct->id,
                    'last_sync_attempt' => $connectionPairProduct->last_sync_attempt,
                    'event' => $event
                ]);
                return;
            }

            // Dispatch sync job
            $syncService->dispatchSyncJob($connectionPairProduct);

        } catch (\Exception $e) {
            $this->handleSyncError($connectionPairProduct, $e);
        }
    }



    /**
     * Handle sync errors
     */
    private function handleSyncError(ConnectionPairProduct $connectionPairProduct, \Exception $e): void
    {
        $syncStatusManager = app(SyncStatusManager::class);
        $syncStatusManager->markAsFailed($connectionPairProduct, $e->getMessage(), 'Observer sync dispatch failed');

        Log::error('Sync job dispatch failed', [
            'connection_pair_product_id' => $connectionPairProduct->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Handle Amazon deletion when catalog_status changes from 'in_catalog' to 'queued'
     */
    private function handleAmazonDeletion(ConnectionPairProduct $connectionPairProduct): void
    {
        try {
            // Get the connection pair
            $connectionPair = $connectionPairProduct->connectionPair;
            if (!$connectionPair) {
                Log::warning('Connection pair not found for Amazon deletion', [
                    'connection_pair_product_id' => $connectionPairProduct->id
                ]);
                return;
            }

            // Check if this is an Amazon connection
            if ($connectionPair->destination_type !== 'amazon') {
                Log::info('Skipping Amazon deletion for non-Amazon connection', [
                    'connection_pair_product_id' => $connectionPairProduct->id,
                    'destination_type' => $connectionPair->destination_type
                ]);
                return;
            }

            // Get the related product
            $product = $connectionPairProduct->product;
            if (!$product) {
                Log::warning('Product not found for Amazon deletion', [
                    'connection_pair_product_id' => $connectionPairProduct->id
                ]);
                return;
            }

            Log::info('Initiating Amazon deletion for product status change', [
                'connection_pair_product_id' => $connectionPairProduct->id,
                'product_id' => $product->id,
                'connection_pair_id' => $connectionPair->id,
                'sku' => $connectionPairProduct->sku
            ]);

            // Create Amazon API client and delete from catalog
            $amazonClient = new \App\Services\Api\AmazonApiClient($connectionPair);
            $result = $amazonClient->deleteFromSellerCatalog($product);

            if ($result) {
                Log::info('Successfully initiated Amazon deletion', [
                    'connection_pair_product_id' => $connectionPairProduct->id,
                    'product_id' => $product->id
                ]);
            } else {
                Log::warning('Amazon deletion returned false result', [
                    'connection_pair_product_id' => $connectionPairProduct->id,
                    'product_id' => $product->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to delete product from Amazon', [
                'connection_pair_product_id' => $connectionPairProduct->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Optionally, you could update the product with an error status
            // $connectionPairProduct->update(['sync_error' => 'Amazon deletion failed: ' . $e->getMessage()]);
        }
    }
}
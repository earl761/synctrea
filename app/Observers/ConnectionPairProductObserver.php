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
}
<?php

namespace App\Observers;

use App\Models\ConnectionPairProduct;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ConnectionPairProductObserver
{
    /**
     * Handle the ConnectionPairProduct "created" event.
     */
    public function created(ConnectionPairProduct $connectionPairProduct): void
    {
        $this->checkAndSyncWithCatalog($connectionPairProduct);
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

        // Check if catalog_status was changed to 'in_catalog'
        if ($connectionPairProduct->isDirty('catalog_status') && 
            $connectionPairProduct->catalog_status === 'in_catalog') {
            $this->checkAndSyncWithCatalog($connectionPairProduct);
        }
    }

    /**
     * Check conditions and sync with catalog if needed
     */
    private function checkAndSyncWithCatalog(ConnectionPairProduct $connectionPairProduct): void
    {
        try {
            // Validate sync conditions
            if (!$this->validateSyncConditions($connectionPairProduct)) {
                return;
            }

            // Get the connection pair and validate relationships
            $connectionPair = $connectionPairProduct->connectionPair;
            $company = $connectionPair->company;
            $product = $connectionPairProduct->product;

            if (!$this->validateRelationships($connectionPair, $company, $product)) {
                return;
            }

            // Check for recent sync attempts to prevent duplicates
            if ($this->hasRecentSyncAttempt($connectionPairProduct)) {
                return;
            }

            // Update sync status to processing
            $connectionPairProduct->update([
                'sync_status' => 'processing',
                'last_sync_attempt' => now(),
                'sync_error' => null
            ]);

            // Dispatch sync job to queue
            dispatch(function () use ($connectionPair, $connectionPairProduct) {
                try {
                    Artisan::call('amazon:sync-catalog', [
                        '--connection-pair-id' => $connectionPair->id,
                        '--product-id' => $connectionPairProduct->product_id
                    ]);

                    $connectionPairProduct->update([
                        'sync_status' => 'completed',
                        'last_synced_at' => now()
                    ]);

                    Log::info('Successfully synced product with catalog', [
                        'connection_pair_product_id' => $connectionPairProduct->id,
                        'connection_pair_id' => $connectionPair->id
                    ]);
                } catch (\Exception $e) {
                    $this->handleSyncError($connectionPairProduct, $e);
                }
            })->onQueue('catalog-sync');

        } catch (\Exception $e) {
            $this->handleSyncError($connectionPairProduct, $e);
        }
    }

    /**
     * Validate basic sync conditions
     */
    private function validateSyncConditions(ConnectionPairProduct $connectionPairProduct): bool
    {
        // Check if item is already synced
        if (!is_null($connectionPairProduct->last_synced_at)) {
            Log::info('Skipping sync - already synced', [
                'connection_pair_product_id' => $connectionPairProduct->id
            ]);
            return false;
        }

        // Validate catalog status
        if ($connectionPairProduct->catalog_status !== 'in_catalog') {
            Log::info('Skipping sync - not in catalog', [
                'connection_pair_product_id' => $connectionPairProduct->id,
                'status' => $connectionPairProduct->catalog_status
            ]);
            return false;
        }

        return true;
    }

    /**
     * Validate relationships and their states
     */
    private function validateRelationships($connectionPair, $company, $product): bool
    {
        if (!$connectionPair) {
            Log::error('Connection pair not found');
            return false;
        }

        if (!$company) {
            Log::error('Company not found', [
                'connection_pair_id' => $connectionPair->id
            ]);
            return false;
        }

        if (!$company->isSubscriptionActive()) {
            Log::info('Skipping sync - inactive subscription', [
                'company_id' => $company->id
            ]);
            return false;
        }

        if (!$product) {
            Log::error('Product not found', [
                'connection_pair_id' => $connectionPair->id
            ]);
            return false;
        }

        return true;
    }

    /**
     * Check for recent sync attempts to prevent duplicates
     */
    private function hasRecentSyncAttempt(ConnectionPairProduct $connectionPairProduct): bool
    {
        if ($connectionPairProduct->last_sync_attempt &&
            $connectionPairProduct->last_sync_attempt->diffInMinutes(now()) < 5) {
            Log::info('Skipping sync - recent attempt exists', [
                'connection_pair_product_id' => $connectionPairProduct->id,
                'last_attempt' => $connectionPairProduct->last_sync_attempt
            ]);
            return true;
        }

        return false;
    }

    /**
     * Handle sync errors
     */
    private function handleSyncError(ConnectionPairProduct $connectionPairProduct, \Exception $e): void
    {
        Log::error('Failed to sync product with catalog', [
            'connection_pair_product_id' => $connectionPairProduct->id,
            'error' => $e->getMessage()
        ]);

        $connectionPairProduct->update([
            'sync_status' => 'failed',
            'sync_error' => $e->getMessage(),
            'last_sync_attempt' => now()
        ]);
    }
}
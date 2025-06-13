<?php

namespace App\Observers;

use App\Models\ConnectionPairProduct;
use Illuminate\Support\Facades\Artisan;

class PrestaShopConnectionPairProductObserver
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
        // Only process items that haven't been synced yet
        if (!is_null($connectionPairProduct->last_synced_at)) {
            return;
        }

        // Make sure catalog_status is 'in_catalog'
        if ($connectionPairProduct->catalog_status !== 'in_catalog') {
            return;
        }

        // Get the connection pair and check owner is active
        $connectionPair = $connectionPairProduct->connectionPair;
        if (!$connectionPair) {
            return;
        }

        // Check if company exists and has an active subscription
        $company = $connectionPair->company;
        if (!$company || !$company->isSubscriptionActive()) {
            return;
        }

        // Run the sync command for this specific item
        Artisan::call('sync:prestashop-products', [
            '--connection-pair-id' => $connectionPair->id
        ]);
    }
}
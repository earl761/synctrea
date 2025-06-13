<?php

namespace App\Observers;

use App\Models\ConnectionPair;
use App\Models\Supplier;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ConnectionPairObserver
{
    /**
     * Handle the ConnectionPair "created" event.
     */
    public function created(ConnectionPair $connectionPair): void
    {
        // Get the supplier type
        $supplierType = $connectionPair->supplier?->type;

        // Only sync for supported supplier types
        if (!in_array($supplierType, [Supplier::TYPE_INGRAM_MICRO, Supplier::TYPE_DH])) {
            Log::info('Skipping product sync - unsupported supplier type', [
                'connection_pair_id' => $connectionPair->id,
                'supplier_type' => $supplierType
            ]);
            return;
        }

        try {
            Log::info('Starting product sync for new connection pair', [
                'connection_pair_id' => $connectionPair->id,
                'supplier_type' => $supplierType
            ]);

            // Run the sync command for this supplier
            Artisan::call('sync:connection-pair-products', [
                '--supplier' => $supplierType
            ]);

            Log::info('Product sync completed for new connection pair', [
                'connection_pair_id' => $connectionPair->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync products for new connection pair', [
                'connection_pair_id' => $connectionPair->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
<?php

namespace App\Console\Commands;

use App\Models\ConnectionPair;
use App\Models\ConnectionPairProduct;
use App\Services\Api\AmazonApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SyncAmazonCatalogCommand
 *
 * Processes all pending products individually to check if they exist in the Amazon catalog.
 * Lists existing products using addToSellerCatalog; marks non-existing ones for creation.
 *
 * Database Interactions:
 * - Table: connection_pair_product
 *   - Reads/Updates: catalog_status, sync_status, sync_error, last_synced_at
 * - Table: connection_pairs
 *   - Reads: connection configuration for Amazon API
 *
 * States:
 * - catalog_status: 'pending' (needs check), 'in_catalog' (matched), 'pending_creation' (needs creation), 'not_in_catalog' (invalid)
 * - sync_status: 'synced' (listed), 'pending' (in progress), 'failed' (error)
 * - last_synced_at: Timestamp of last successful sync
 * - sync_error: Stores listing errors
 *
 * Example Usage:
 * ```
 * php artisan amazon:sync-catalog --connection-pair-id=1
 * ```
 */
class SyncAmazonCatalogCommand extends Command
{
    protected $signature = 'amazon:sync-catalog {--connection-pair-id= : The connection pair ID to sync}';

    protected $description = 'Process all pending products to check catalog and list if found';

    public function handle()
    {
        $connectionPairId = $this->option('connection-pair-id');
        if (!$connectionPairId) {
            $this->error('Connection pair ID is required');
            Log::error('Missing connection pair ID', ['command' => 'amazon:sync-catalog']);
            return 1;
        }

        try {
            $connectionPair = ConnectionPair::findOrFail($connectionPairId);
            $client = new AmazonApiClient($connectionPair);

            // Fetch all pending products
            $products = ConnectionPairProduct::where('connection_pair_id', $connectionPairId)
                ->with('product')
                ->inCatalog()
                ->get();

            if ($products->isEmpty()) {
                $this->info('No products pending catalog check');
                Log::info('No products to check', ['connection_pair_id' => $connectionPairId]);
                return 0;
            }

            $this->info("Found {$products->count()} products to process");

            foreach ($products as $product) {
                $sku = $product->sku;
                $upc = $product->product->upc;
                $this->info("Processing product: {$sku} (UPC: {$upc})");

                // Validate UPC
                if (empty($upc) || !preg_match('/^\d{12,13}$/', $upc)) {
                    $this->error("❌ Invalid or empty UPC for {$sku}");
                    Log::warning('Invalid or empty UPC', [
                        'connection_pair_product_id' => $product->id,
                        'sku' => $sku,
                        'upc' => $upc,
                        'connection_pair_id' => $connectionPairId,
                    ]);
                    $product->update(['catalog_status' => 'not_in_catalog']);
                    continue;
                }

                // Check catalog data
                $catalogData = $product->product->catalog_data ?? [];
                $catalogItem = $catalogData['items'][0] ?? null;
                $asin = $catalogItem['asin'] ?? null;

                if (!$asin) {
                    // No catalog data, check catalog via API
                    $this->info("Checking Amazon catalog for UPC: {$upc}...");

                    $catalogResult = $client->searchCatalogItemByUpc($upc);

                    if (!$catalogResult || 
                    empty($catalogResult['items']) || 
                    !isset($catalogResult['items'][0]['asin'])) {
                        $this->error("❌ UPC not found in Amazon catalog: {$upc}");
                        Log::warning('UPC not found in catalog', [
                            'connection_pair_product_id' => $product->id,
                            'sku' => $sku,
                            'upc' => $upc,
                            'connection_pair_id' => $connectionPairId,
                        ]);
                       // $product->update(['catalog_status' => 'pending_creation']);
                        continue;
                    }

                    $asin = $catalogResult['items'][0]['asin'] ?? null;
                    //$product->product->update(['catalog_data' => $catalogResult['catalog_item']]);
                    $this->info("✅ Found in catalog: ASIN {$asin}");
                } else {
                    $this->info("✅ Using existing catalog data: ASIN {$asin}");
                }

                // List the product
                $this->info("Listing product: {$sku}...");
                try {
                    $success = $client->addToSellerCatalog(
                        $product->product, 
                        $asin,
                        floatval($product->final_price),
                        intval($product->stock),
                        $product->product->category ?? 'Unknown'
                    );

                    if ($success) {
                        $product->update([
                            'catalog_status' => 'in_catalog',
                            'sync_status' => 'synced',
                            'last_synced_at' => now(),
                           
                        ]);
                        $this->info("✅ {$sku} synced successfully");
                        Log::info('Product synced successfully', [
                            'connection_pair_product_id' => $product->id,
                            'sku' => $sku,
                            'upc' => $upc,
                            'asin' => $asin,
                            'connection_pair_id' => $connectionPairId,
                        ]);
                    } else {
                        throw new \Exception('Listing failed with no error message');
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Listing failed for {$sku}: {$e->getMessage()}");
                    Log::error('Listing failed', [
                        'connection_pair_product_id' => $product->id,
                        'sku' => $sku,
                        'upc' => $upc,
                        'asin' => $asin,
                        'error' => $e->getMessage(),
                        'connection_pair_id' => $connectionPairId,
                    ]);
                    $product->update([
                        'sync_status' => 'failed',
                        'sync_error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info('All products processed');
            return 0;
        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");
            Log::error('SyncAmazonCatalogCommand failed', [
                'connection_pair_id' => $connectionPairId,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }
}
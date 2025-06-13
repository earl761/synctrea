<?php

namespace App\Console\Commands;

use App\Models\ConnectionPair;
use App\Models\Product;
use App\Services\Api\PrestaShopApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPrestaShopProductsCommand extends Command
{
    protected $signature = 'sync:prestashop-products
        {--connection-pair-id= : ID of the specific connection pair to sync}';
    protected $description = 'Synchronize products with PrestaShop destinations';

    public function handle(): void
    {
        $this->info('Starting PrestaShop product synchronization...');

        try {
            $query = ConnectionPair::whereHas('destination', function ($query) {
                $query->where('type', 'prestashop');
            })->with(['destination', 'products']);

            // Filter by connection pair ID if provided
            if ($connectionPairId = $this->option('connection-pair-id')) {
                $query->where('id', $connectionPairId);
            }

            $pairs = $query->get();

            foreach ($pairs as $pair) {
               // $this->info("Processing connection pair: {$pair->name}");
                
                $client = new PrestaShopApiClient($pair->destination);
                $client->initialize();
                
                foreach ($pair->products as $product) {
                    try {
                        // Get the connection pair product record first
                        $connectionPairProduct = $product->connectionPairProducts()
                            ->where('connection_pair_id', $pair->id)
                            ->first();

                        if (!$connectionPairProduct) {
                            $this->error("❌ No connection pair product found for {$product->sku}");
                            continue;
                        }

                        $this->info("Syncing product: {$connectionPairProduct->sku}");
                        
                        $productData = [
                            'sku' => $connectionPairProduct->sku,
                            'reference' => $connectionPairProduct->sku,
                            'name' => $connectionPairProduct->name,
                            'description' => $product->description,
                            'price' => $connectionPairProduct->final_price,
                            'quantity' => $connectionPairProduct->stock
                        ];

                        try {
                            $result = $client->updateProduct($productData);
                            
                            // Update sync status on success
                            $connectionPairProduct->update([
                                'sync_status' => 'synced',
                                'last_synced_at' => now(),
                                'sync_error' => null,
                                'last_sync_attempt' => now()
                            ]);
                            
                            $this->info("✅ Successfully synced: {$connectionPairProduct->sku}");
                        } catch (\Exception $e) {
                            // Update sync status on failure
                            $connectionPairProduct->update([
                                'sync_status' => 'failed',
                                'sync_error' => $e->getMessage(),
                                'last_sync_attempt' => now()
                            ]);

                            $this->error("❌ Error syncing product {$connectionPairProduct->sku}: {$e->getMessage()}");
                            Log::error("PrestaShop sync error for {$connectionPairProduct->sku}", [
                                'error' => $e->getMessage(),
                                'connection_pair_id' => $pair->id,
                                'product_id' => $product->id,
                                'sku' => $connectionPairProduct->sku
                            ]);
                            continue;
                        }
                    } catch (\Exception $e) {
                        $this->error("Error processing product {$product->sku}: {$e->getMessage()}");
                        Log::error("PrestaShop product processing error", [
                            'error' => $e->getMessage(),
                            'connection_pair_id' => $pair->id,
                            'product_id' => $product->id,
                            'sku' => $product->sku
                        ]);
                        continue;
                    }
                }
            }

            $this->info('✅ PrestaShop product synchronization completed.');
        } catch (\Exception $e) {
            $this->error("❌ Error during synchronization: {$e->getMessage()}");
            Log::error("PrestaShop sync error", ['error' => $e->getMessage()]);
        }
    }
}
<?php

namespace App\Console\Commands;

use App\Models\ConnectionPair;
use App\Models\Product;
use App\Services\Api\PrestaShopApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPrestaShopProductsCommand extends Command
{
    protected $signature = 'sync:prestashop-products';
    protected $description = 'Synchronize products with PrestaShop destinations';

    public function handle(): void
    {
        $this->info('Starting PrestaShop product synchronization...');

        try {
            $pairs = ConnectionPair::whereHas('destination', function ($query) {
                $query->where('type', 'prestashop');
            })->with(['destination', 'products'])->get();

            foreach ($pairs as $pair) {
                $this->info("Processing connection pair: {$pair->name}");
                
                $client = new PrestaShopApiClient($pair->destination);
                
                foreach ($pair->products as $product) {
                    try {
                        $this->info("Syncing product: {$product->sku}");
                        
                        $productData = [
                            'reference' => $product->sku,
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'quantity' => $product->stock_quantity
                        ];

                        $client->updateProduct($productData);
                        
                        $this->info("Successfully synced: {$product->sku}");
                    } catch (\Exception $e) {
                        $this->error("Error syncing product {$product->sku}: {$e->getMessage()}");
                        Log::error("PrestaShop sync error for {$product->sku}: {$e->getMessage()}");
                        continue;
                    }
                }
            }

            $this->info('PrestaShop product synchronization completed.');
        } catch (\Exception $e) {
            $this->error("Error during synchronization: {$e->getMessage()}");
            Log::error("PrestaShop sync error: {$e->getMessage()}");
        }
    }
}
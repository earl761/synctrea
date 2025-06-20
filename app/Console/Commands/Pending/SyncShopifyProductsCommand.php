<?php

namespace App\Console\Commands;

use App\Models\ConnectionPair;
use App\Models\Product;
use App\Services\Api\ShopifyApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncShopifyProductsCommand extends Command
{
    protected $signature = 'sync:shopify-products';
    protected $description = 'Synchronize products with Shopify destinations';

    public function handle(): void
    {
        $this->info('Starting Shopify product synchronization...');

        try {
            $pairs = ConnectionPair::whereHas('destination', function ($query) {
                $query->where('type', 'shopify');
            })->with(['destination', 'products'])->get();

            foreach ($pairs as $pair) {
                $this->info("Processing connection pair: {$pair->name}");
                
                $client = new ShopifyApiClient($pair->destination);
                
                foreach ($pair->products as $product) {
                    try {
                        $this->info("Syncing product: {$product->sku}");
                        
                        $productData = [
                            'sku' => $product->sku,
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'quantity' => $product->stock_quantity
                        ];

                        $client->updateProduct($productData);
                        
                        $this->info("Successfully synced: {$product->sku}");
                    } catch (\Exception $e) {
                        $this->error("Error syncing product {$product->sku}: {$e->getMessage()}");
                        Log::error("Shopify sync error for {$product->sku}: {$e->getMessage()}");
                        continue;
                    }
                }
            }

            $this->info('Shopify product synchronization completed.');
        } catch (\Exception $e) {
            $this->error("Error during synchronization: {$e->getMessage()}");
            Log::error("Shopify sync error: {$e->getMessage()}");
        }
    }
}
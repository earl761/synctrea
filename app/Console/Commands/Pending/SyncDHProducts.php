<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Services\Api\DHApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDHProducts extends Command
{
    protected $signature = 'dh:sync-products';
    protected $description = 'Synchronize products from D&H API';

    public function handle()
    {
        $supplier = Supplier::where('type', 'dh')->first();
        if (!$supplier) {
            $this->error('D&H supplier not found');
            return 1;
        }

        $client = new DHApiClient($supplier);

        if (!$client->authenticate()) {
            $this->error('Failed to authenticate with D&H API');
            return 1;
        }

        $this->info('Starting D&H product synchronization...');

        try {
            $products = $client->getProducts();
            if (!$products) {
                $this->error('Failed to fetch products from D&H API');
                return 1;
            }

            // Process products here
            foreach ($products as $productData) {
                // Update or create product
                $product = $supplier->products()->updateOrCreate(
                    ['sku' => $productData['sku']],
                    [
                        'name' => $productData['name'],
                        'description' => $productData['description'] ?? null,
                        'manufacturer' => $productData['manufacturer'] ?? null,
                        'category' => $productData['category'] ?? null,
                        'raw_data' => $productData
                    ]
                );

                $this->line("Processed product: {$product->sku}");
            }

            $this->info('D&H product synchronization completed successfully');
            return 0;
        } catch (\Exception $e) {
            Log::error('D&H product sync error', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage()
            ]);
            $this->error('Error during product synchronization: ' . $e->getMessage());
            return 1;
        }
    }
}
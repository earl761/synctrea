<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Services\Api\DHApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDHPricing extends Command
{
    protected $signature = 'dh:sync-pricing';
    protected $description = 'Synchronize pricing from D&H API';

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

        $this->info('Starting D&H pricing synchronization...');

        try {
            // Get all product SKUs from the database
            $skus = $supplier->products()->pluck('sku')->toArray();

            // Process SKUs in chunks to avoid overwhelming the API
            foreach (array_chunk($skus, 100) as $skuChunk) {
                $pricing = $client->getPricing($skuChunk);
                if (!$pricing) {
                    $this->warn('Failed to fetch pricing for some SKUs');
                    continue;
                }

                foreach ($pricing as $item) {
                    $product = $supplier->products()->where('sku', $item['sku'])->first();
                    if ($product) {
                        $product->update([
                            'cost_price' => $item['cost_price'] ?? 0,
                            'msrp' => $item['msrp'] ?? 0,
                            'last_price_sync' => now()
                        ]);
                        $this->line("Updated pricing for SKU: {$item['sku']}");
                    }
                }
            }

            $this->info('D&H pricing synchronization completed successfully');
            return 0;
        } catch (\Exception $e) {
            Log::error('D&H pricing sync error', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage()
            ]);
            $this->error('Error during pricing synchronization: ' . $e->getMessage());
            return 1;
        }
    }
}
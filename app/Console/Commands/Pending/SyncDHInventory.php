<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Services\Api\DHApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDHInventory extends Command
{
    protected $signature = 'dh:sync-inventory';
    protected $description = 'Synchronize inventory from D&H API';

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

        $this->info('Starting D&H inventory synchronization...');

        try {
            // Get all product SKUs from the database
            $skus = $supplier->products()->pluck('sku')->toArray();

            // Process SKUs in chunks to avoid overwhelming the API
            foreach (array_chunk($skus, 100) as $skuChunk) {
                $inventory = $client->getInventory($skuChunk);
                if (!$inventory) {
                    $this->warn('Failed to fetch inventory for some SKUs');
                    continue;
                }

                foreach ($inventory as $item) {
                    $product = $supplier->products()->where('sku', $item['sku'])->first();
                    if ($product) {
                        $product->update([
                            'stock_quantity' => $item['quantity'] ?? 0,
                            'inventory_status' => $item['status'] ?? null,
                            'last_inventory_sync' => now()
                        ]);
                        $this->line("Updated inventory for SKU: {$item['sku']}");
                    }
                }
            }

            $this->info('D&H inventory synchronization completed successfully');
            return 0;
        } catch (\Exception $e) {
            Log::error('D&H inventory sync error', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage()
            ]);
            $this->error('Error during inventory synchronization: ' . $e->getMessage());
            return 1;
        }
    }
}
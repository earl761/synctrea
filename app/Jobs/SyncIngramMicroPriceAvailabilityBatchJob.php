<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SyncLog;
use App\Services\Api\IngramMicroApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncIngramMicroPriceAvailabilityBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 300;
    public $backoff = [30, 60, 120]; // Exponential backoff in seconds

    public function __construct(
        private Collection $products,
        private Supplier $supplier,
        private string $syncLogId
    ) {}

    public function handle(): void
    {
        try {
            Log::info('Starting batch price/availability sync', [
                'product_count' => $this->products->count(),
                'supplier_id' => $this->supplier->id,
                'sync_log_id' => $this->syncLogId
            ]);

            $client = new IngramMicroApiClient($this->supplier);
            $client->initialize();

            // Prepare product data for API call
            $productData = [];
            foreach ($this->products as $product) {
                $identifier = [];
                if (!empty($product->sku)) {
                    $identifier['ingramPartNumber'] = $product->sku;
                } elseif (!empty($product->part_number)) {
                    $identifier['vendorPartNumber'] = $product->part_number;
                } elseif (!empty($product->upc)) {
                    $identifier['upc'] = $product->upc;
                }
                
                if (!empty($identifier)) {
                    $productData[] = $identifier;
                }
            }

            if (empty($productData)) {
                Log::warning('No valid product identifiers found in batch');
                return;
            }

            // Make API call for price and availability
            $result = $client->getPriceAndAvailability([
                'products' => $productData,
                'includeAvailability' => 'true',
                'includePricing' => 'true',
                'showAvailableDiscounts' => 'true',
            ]);

            $updatedCount = 0;

            DB::beginTransaction();
            try {
                foreach ($result as $item) {
                    $sku = $item['ingramPartNumber'] ?? null;
                    if (!$sku) continue;
                    
                    $product = $this->products->firstWhere('sku', $sku);
                    if (!$product) continue;

                    $pricing = $item['pricing'] ?? [];
                    $availability = $item['availability'] ?? [];

                    $product->update([
                        'cost_price' => $pricing['customerPrice'] ?? 0,
                        'currency_code' => $pricing['currencyCode'] ?? 'USD',
                        'retail_price' => $pricing['retailPrice'] ?? 0,
                        'map_price' => $pricing['mapPrice'] ?? 0,
                        'stock_quantity' => $availability['totalAvailability'] ?? 0,
                        'metadata' => array_merge(
                            $product->metadata ?? [],
                            [
                                'pricing' => $pricing,
                                'availability' => $availability,
                            ]
                        ),
                    ]);

                    $updatedCount++;
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            // Dispatch individual product detail jobs for each product
            foreach ($this->products as $product) {
                if (!empty($product->sku)) {
                    SyncIngramMicroProductDetailsJob::dispatch(
                        $product,
                        $this->supplier,
                        $this->syncLogId
                    )->onQueue('product-details');
                }
            }

            Log::info('Batch price/availability sync completed', [
                'updated_count' => $updatedCount,
                'total_products' => $this->products->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Batch price/availability sync failed', [
                'error' => $e->getMessage(),
                'supplier_id' => $this->supplier->id,
                'product_count' => $this->products->count()
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Batch price/availability sync job failed', [
            'error' => $exception->getMessage(),
            'supplier_id' => $this->supplier->id,
            'product_count' => $this->products->count(),
            'sync_log_id' => $this->syncLogId
        ]);
    }
}
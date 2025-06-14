<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\Api\IngramMicroApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncIngramMicroProductDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 180; // Increased timeout for rate limiting delays
    public $backoff = [30, 60, 120]; // Longer backoff for Ingram Micro rate limits

    public function __construct(
        private Product $product,
        private Supplier $supplier,
        private string $syncLogId
    ) {}

    public function handle(): void
    {
        try {
            Log::info('Starting product details sync', [
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'supplier_id' => $this->supplier->id
            ]);

            $client = new IngramMicroApiClient($this->supplier);
            $client->initialize();

            // Get product details from API
            $productDetails = $client->getProductDetails($this->product->sku);

            // Extract additional information
            $additionalInfo = $productDetails['additionalInformation'] ?? [];
            $productWeight = $additionalInfo['productWeight'][0] ?? [];

            $updateData = [
                'weight' => $productWeight['weight'] ?? null,
                'weight_unit' => $productWeight['weightUnit'] ?? null,
                'height' => $additionalInfo['height'] ?? null,
                'width' => $additionalInfo['width'] ?? null,
                'length' => $additionalInfo['length'] ?? null,
                'net_weight' => $additionalInfo['netWeight'] ?? null,
                'dimension_unit' => $additionalInfo['dimensionUnit'] ?? null,
            ];

            // Update product with details
            $this->product->update($updateData);

            Log::info('Product details sync completed', [
                'product_id' => $this->product->id,
                'sku' => $this->product->sku
            ]);

        } catch (\Exception $e) {
            Log::error('Product details sync failed', [
                'error' => $e->getMessage(),
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'supplier_id' => $this->supplier->id
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Product details sync job failed', [
            'error' => $exception->getMessage(),
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'supplier_id' => $this->supplier->id,
            'sync_log_id' => $this->syncLogId
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }
}
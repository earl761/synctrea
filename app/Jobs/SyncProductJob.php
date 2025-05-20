<?php

namespace App\Jobs;

use App\Models\ConnectionPair;
use App\Models\Product;
use App\Models\SyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 300;

    public function __construct(
        private $client,
        private array $productData,
        private ConnectionPair $connectionPair,
        private Product $product
    ) {}

    public function handle(): void
    {
        try {
            Log::info("Starting sync for product {$this->product->sku}", [
                'connection_pair_id' => $this->connectionPair->id,
                'product_id' => $this->product->id
            ]);

            // Attempt to sync the product
            $result = $this->client->updateProduct($this->productData);

            // Log successful sync
            SyncLog::create([
                'connection_pair_id' => $this->connectionPair->id,
                'product_id' => $this->product->id,
                'status' => 'success',
                'message' => 'Product synchronized successfully',
                'response_data' => $result
            ]);

            Log::info("Successfully synced product {$this->product->sku}");
        } catch (\Exception $e) {
            Log::error("Failed to sync product {$this->product->sku}", [
                'error' => $e->getMessage(),
                'connection_pair_id' => $this->connectionPair->id,
                'product_id' => $this->product->id
            ]);

            // Log the error
            SyncLog::create([
                'connection_pair_id' => $this->connectionPair->id,
                'product_id' => $this->product->id,
                'status' => 'error',
                'message' => $e->getMessage()
            ]);

            // If we've exhausted all retries, mark the product as failed
            if ($this->attempts() >= $this->tries) {
                $this->product->connectionPairs()->updateExistingPivot(
                    $this->connectionPair->id,
                    ['catalog_status' => 'failed']
                );
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed for product {$this->product->sku}", [
            'error' => $exception->getMessage(),
            'connection_pair_id' => $this->connectionPair->id,
            'product_id' => $this->product->id
        ]);

        // Update the product's catalog status
        $this->product->connectionPairs()->updateExistingPivot(
            $this->connectionPair->id,
            ['catalog_status' => 'failed']
        );
    }
} 
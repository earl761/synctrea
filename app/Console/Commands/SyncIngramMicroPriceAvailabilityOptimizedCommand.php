<?php

namespace App\Console\Commands;

use App\Jobs\SyncIngramMicroPriceAvailabilityBatchJob;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class SyncIngramMicroPriceAvailabilityOptimizedCommand extends Command
{
    protected $signature = 'ingram:sync-price-availability-optimized
        {--batch-size=50 : Number of products to process per batch (max 50)}
        {--max-concurrent=5 : Maximum number of concurrent batch jobs}
        {--force : Force sync even if there was a recent successful sync}
        {--queue=default : Queue name for batch jobs}
        {--details-queue=product-details : Queue name for product details jobs}';

    protected $description = 'Optimized sync of product prices and availability from Ingram Micro using parallel processing';

    public function handle(): int
    {
        // Get the supplier first to ensure it exists before creating the sync log
        $supplier = Supplier::where('type', 'ingram_micro')
            ->where('is_active', true)
            ->firstOrFail();

        Log::info('Starting optimized Ingram Micro sync', [
            'supplier_id' => $supplier->id
        ]);

        // Create a new sync log for this sync
        $syncLog = new SyncLog([
            'supplier_id' => $supplier->id,
            'type' => 'ingram_micro_price_availability_optimized',
            'status' => 'running',
            'started_at' => now(),
        ]);
        $syncLog->save();

        try {
            // Check for recent successful sync unless forced
            if (!$this->option('force')) {
                $recentSync = SyncLog::where('type', 'ingram_micro_price_availability_optimized')
                    ->where('status', 'completed')
                    ->where('created_at', '>=', now()->subHours(1))
                    ->exists();

                if ($recentSync) {
                    $this->warn('A successful sync was performed in the last hour. Use --force to override.');
                    $syncLog->update([
                        'status' => 'skipped',
                        'completed_at' => now(),
                        'metadata' => ['reason' => 'Recent sync exists']
                    ]);
                    return Command::FAILURE;
                }
            }

            $batchSize = min((int) $this->option('batch-size'), 50);
            $maxConcurrent = (int) $this->option('max-concurrent');
            $queueName = $this->option('queue');
            $detailsQueueName = $this->option('details-queue');

            $this->info('Starting optimized Ingram Micro price and availability sync...');
            $this->info("Batch size: {$batchSize}, Max concurrent: {$maxConcurrent}");

            // Get total count of products to process
            $totalProducts = Product::where('supplier_id', $supplier->id)
                ->whereNotNull('sku')
                ->count();

            if ($totalProducts === 0) {
                $this->warn('No products found to sync.');
                $syncLog->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'metadata' => ['total_processed' => 0, 'total_batches' => 0]
                ]);
                return Command::SUCCESS;
            }

            $this->info("Found {$totalProducts} products to sync");

            $totalBatches = 0;
            $dispatchedJobs = 0;

            // Process products in batches and dispatch jobs
            Product::where('supplier_id', $supplier->id)
                ->whereNotNull('sku')
                ->chunkById($batchSize, function ($products) use (
                    $supplier, 
                    $syncLog, 
                    $queueName, 
                    $maxConcurrent, 
                    &$totalBatches, 
                    &$dispatchedJobs
                ) {
                    $totalBatches++;
                    
                    // Wait if we've reached max concurrent jobs
                    while ($dispatchedJobs >= $maxConcurrent) {
                        $this->info("Waiting for queue capacity... (dispatched: {$dispatchedJobs}/{$maxConcurrent})");
                        sleep(2);
                        
                        // Check queue size to determine if we can dispatch more
                        $queueSize = Queue::size($queueName);
                        if ($queueSize < $maxConcurrent) {
                            $dispatchedJobs = $queueSize;
                        }
                    }

                    // Dispatch batch job
                    SyncIngramMicroPriceAvailabilityBatchJob::dispatch(
                        $products,
                        $supplier,
                        $syncLog->id
                    )->onQueue($queueName);

                    $dispatchedJobs++;
                    
                    $this->info("Dispatched batch {$totalBatches} with {$products->count()} products");
                });

            $this->info("Dispatched {$totalBatches} batch jobs for price/availability sync");
            $this->info("Product details will be processed on the '{$detailsQueueName}' queue");

            // Update sync log with initial completion
            $syncLog->update([
                'status' => 'processing',
                'metadata' => [
                    'total_products' => $totalProducts,
                    'total_batches' => $totalBatches,
                    'batch_size' => $batchSize,
                    'max_concurrent' => $maxConcurrent,
                    'queue_name' => $queueName,
                    'details_queue_name' => $detailsQueueName,
                    'dispatched_at' => now()->toISOString()
                ]
            ]);

            $this->info('All batch jobs have been dispatched to the queue.');
            $this->info('Monitor the queue workers to track progress:');
            $this->info("  php artisan queue:work --queue={$queueName},{$detailsQueueName}");
            $this->info('Check sync log ID: ' . $syncLog->id);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Optimized Ingram Micro sync failed: ' . $e->getMessage(), [
                'exception' => $e,
                'sync_log_id' => $syncLog->id
            ]);

            $syncLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error' => $e->getMessage(),
            ]);

            $this->error('Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
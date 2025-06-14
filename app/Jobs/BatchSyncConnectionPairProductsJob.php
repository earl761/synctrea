<?php

namespace App\Jobs;

use App\Models\ConnectionPairProduct;
use App\Services\SyncService;
use App\Services\SyncStatusManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

class BatchSyncConnectionPairProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 2;
    public $timeout = 300; // 5 minutes
    public $backoff = [30, 60, 120]; // Exponential backoff

    protected Collection $connectionPairProductIds;
    protected string $syncType;
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(Collection $connectionPairProductIds, string $syncType = 'catalog', array $options = [])
    {
        $this->connectionPairProductIds = $connectionPairProductIds;
        $this->syncType = $syncType;
        $this->options = $options;
        $this->onQueue('sync-batch');
    }

    /**
     * Execute the job.
     */
    public function handle(SyncService $syncService, SyncStatusManager $statusManager): void
    {
        $startTime = microtime(true);
        $processedCount = 0;
        $errorCount = 0;

        Log::info('Starting batch sync job', [
            'job_id' => $this->job->getJobId(),
            'sync_type' => $this->syncType,
            'item_count' => $this->connectionPairProductIds->count(),
            'options' => $this->options
        ]);

        try {
            // Process items in smaller chunks to avoid memory issues
            $this->connectionPairProductIds->chunk(50)->each(function ($chunk) use ($syncService, $statusManager, &$processedCount, &$errorCount) {
                $items = ConnectionPairProduct::whereIn('id', $chunk->toArray())
                    ->with(['connectionPair.company', 'product'])
                    ->get();

                foreach ($items as $item) {
                    try {
                        // Mark as in progress
                        $statusManager->markInProgress($item);

                        // Validate sync conditions
                        if (!$syncService->shouldSyncConnectionPairProduct($item)) {
                            Log::debug('Skipping sync for item', ['id' => $item->id, 'reason' => 'validation_failed']);
                            continue;
                        }

                        // Dispatch individual sync job based on destination type
                        $this->dispatchSyncJob($syncService, $item);
                        
                        $processedCount++;
                        
                        // Add small delay to prevent overwhelming the system
                        if ($processedCount % 10 === 0) {
                            usleep(100000); // 100ms delay every 10 items
                        }
                        
                    } catch (Exception $e) {
                        $errorCount++;
                        $statusManager->markFailed($item, $e->getMessage());
                        
                        Log::error('Failed to process item in batch sync', [
                            'item_id' => $item->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            });

            $duration = microtime(true) - $startTime;
            
            Log::info('Batch sync job completed', [
                'job_id' => $this->job->getJobId(),
                'processed_count' => $processedCount,
                'error_count' => $errorCount,
                'duration_seconds' => round($duration, 2),
                'items_per_second' => $processedCount > 0 ? round($processedCount / $duration, 2) : 0
            ]);
            
        } catch (Exception $e) {
            Log::error('Batch sync job failed', [
                'job_id' => $this->job->getJobId(),
                'error' => $e->getMessage(),
                'processed_count' => $processedCount,
                'error_count' => $errorCount
            ]);
            
            throw $e;
        }
    }

    /**
     * Dispatch appropriate sync job based on destination type
     */
    protected function dispatchSyncJob(SyncService $syncService, ConnectionPairProduct $item): void
    {
        $destination = $item->connectionPair->destination ?? null;
        
        if (!$destination) {
            throw new Exception('No destination found for connection pair');
        }

        switch ($destination->type) {
            case 'amazon':
                $syncService->dispatchAmazonSync($item->connectionPair->id, [
                    'priority' => $this->options['priority'] ?? 'normal',
                    'batch_id' => $this->job->getJobId()
                ]);
                break;
                
            case 'prestashop':
                $syncService->dispatchPrestaShopSync($item->connectionPair->id, [
                    'priority' => $this->options['priority'] ?? 'normal',
                    'batch_id' => $this->job->getJobId()
                ]);
                break;
                
            default:
                throw new Exception("Unsupported destination type: {$destination->type}");
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('Batch sync job failed permanently', [
            'job_id' => $this->job?->getJobId(),
            'sync_type' => $this->syncType,
            'item_count' => $this->connectionPairProductIds->count(),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark all items as failed if the entire batch fails
        $statusManager = app(SyncStatusManager::class);
        
        ConnectionPairProduct::whereIn('id', $this->connectionPairProductIds->toArray())
            ->chunk(100, function ($items) use ($statusManager, $exception) {
                foreach ($items as $item) {
                    $statusManager->markFailed($item, 'Batch job failed: ' . $exception->getMessage());
                }
            });
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'sync',
            'batch',
            $this->syncType,
            'items:' . $this->connectionPairProductIds->count()
        ];
    }
}
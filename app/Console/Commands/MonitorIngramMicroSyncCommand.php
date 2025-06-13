<?php

namespace App\Console\Commands;

use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class MonitorIngramMicroSyncCommand extends Command
{
    protected $signature = 'ingram:monitor-sync
        {sync-log-id? : Specific sync log ID to monitor}
        {--refresh=5 : Refresh interval in seconds}
        {--queue=default : Queue name for batch jobs}
        {--details-queue=product-details : Queue name for product details jobs}';

    protected $description = 'Monitor the progress of Ingram Micro sync operations';

    public function handle(): int
    {
        $syncLogId = $this->argument('sync-log-id');
        $refreshInterval = (int) $this->option('refresh');
        $queueName = $this->option('queue');
        $detailsQueueName = $this->option('details-queue');

        if ($syncLogId) {
            return $this->monitorSpecificSync($syncLogId, $refreshInterval, $queueName, $detailsQueueName);
        }

        return $this->monitorRecentSyncs($refreshInterval, $queueName, $detailsQueueName);
    }

    private function monitorSpecificSync(string $syncLogId, int $refreshInterval, string $queueName, string $detailsQueueName): int
    {
        $syncLog = SyncLog::find($syncLogId);
        
        if (!$syncLog) {
            $this->error("Sync log with ID {$syncLogId} not found.");
            return Command::FAILURE;
        }

        $this->info("Monitoring sync log ID: {$syncLogId}");
        $this->info("Press Ctrl+C to stop monitoring\n");

        while (true) {
            $this->displaySyncStatus($syncLog, $queueName, $detailsQueueName);
            
            // Refresh the sync log
            $syncLog->refresh();
            
            // If sync is completed or failed, stop monitoring
            if (in_array($syncLog->status, ['completed', 'failed', 'skipped'])) {
                $this->info("\nSync {$syncLog->status}. Monitoring stopped.");
                break;
            }

            sleep($refreshInterval);
            $this->output->write("\033[2J\033[H"); // Clear screen
        }

        return Command::SUCCESS;
    }

    private function monitorRecentSyncs(int $refreshInterval, string $queueName, string $detailsQueueName): int
    {
        $this->info("Monitoring recent Ingram Micro syncs");
        $this->info("Press Ctrl+C to stop monitoring\n");

        while (true) {
            $recentSyncs = SyncLog::where('type', 'like', 'ingram_micro%')
                ->where('created_at', '>=', now()->subHours(24))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $this->displayRecentSyncs($recentSyncs, $queueName, $detailsQueueName);

            sleep($refreshInterval);
            $this->output->write("\033[2J\033[H"); // Clear screen
        }

        return Command::SUCCESS;
    }

    private function displaySyncStatus(SyncLog $syncLog, string $queueName, string $detailsQueueName): void
    {
        $metadata = $syncLog->metadata ?? [];
        
        $this->info("=== Sync Status ===");
        $this->info("ID: {$syncLog->id}");
        $this->info("Type: {$syncLog->type}");
        $this->info("Status: {$syncLog->status}");
        $this->info("Started: {$syncLog->started_at}");
        
        if ($syncLog->completed_at) {
            $this->info("Completed: {$syncLog->completed_at}");
            $duration = $syncLog->started_at->diffForHumans($syncLog->completed_at, true);
            $this->info("Duration: {$duration}");
        }

        if (isset($metadata['total_products'])) {
            $this->info("Total Products: {$metadata['total_products']}");
        }
        
        if (isset($metadata['total_batches'])) {
            $this->info("Total Batches: {$metadata['total_batches']}");
        }

        if (isset($metadata['batch_size'])) {
            $this->info("Batch Size: {$metadata['batch_size']}");
        }

        $this->displayQueueStatus($queueName, $detailsQueueName);

        if ($syncLog->error) {
            $this->error("Error: {$syncLog->error}");
        }

        $this->info("\nLast updated: " . now()->format('Y-m-d H:i:s'));
    }

    private function displayRecentSyncs($syncs, string $queueName, string $detailsQueueName): void
    {
        $this->info("=== Recent Ingram Micro Syncs ===");
        
        if ($syncs->isEmpty()) {
            $this->info("No recent syncs found.");
        } else {
            $this->table(
                ['ID', 'Type', 'Status', 'Started', 'Duration', 'Products'],
                $syncs->map(function ($sync) {
                    $metadata = $sync->metadata ?? [];
                    $duration = $sync->completed_at 
                        ? $sync->started_at->diffForHumans($sync->completed_at, true)
                        : 'Running';
                    
                    return [
                        $sync->id,
                        str_replace('ingram_micro_', '', $sync->type),
                        $sync->status,
                        $sync->started_at->format('H:i:s'),
                        $duration,
                        $metadata['total_products'] ?? 'N/A'
                    ];
                })->toArray()
            );
        }

        $this->displayQueueStatus($queueName, $detailsQueueName);
        
        $this->info("\nLast updated: " . now()->format('Y-m-d H:i:s'));
    }

    private function displayQueueStatus(string $queueName, string $detailsQueueName): void
    {
        $this->info("\n=== Queue Status ===");
        
        try {
            $batchQueueSize = Queue::size($queueName);
            $detailsQueueSize = Queue::size($detailsQueueName);
            
            $this->info("Batch Queue ({$queueName}): {$batchQueueSize} jobs pending");
            $this->info("Details Queue ({$detailsQueueName}): {$detailsQueueSize} jobs pending");
            
            $totalPending = $batchQueueSize + $detailsQueueSize;
            $this->info("Total Pending: {$totalPending} jobs");
            
        } catch (\Exception $e) {
            $this->warn("Could not retrieve queue status: " . $e->getMessage());
        }
    }
}
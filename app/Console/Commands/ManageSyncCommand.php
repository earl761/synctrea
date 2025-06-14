<?php

namespace App\Console\Commands;

use App\Jobs\BatchSyncConnectionPairProductsJob;
use App\Models\ConnectionPairProduct;
use App\Services\SyncAnalyticsService;
use App\Services\SyncService;
use App\Services\SyncStatusManager;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ManageSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sync:manage 
                            {action : The action to perform (retry-failed|cleanup|batch-sync|status|analytics)}
                            {--connection-pair-id= : Specific connection pair ID to target}
                            {--hours= : Hours to look back for failed items (default: 24)}
                            {--limit= : Limit number of items to process (default: 100)}
                            {--force : Force the operation without confirmation}
                            {--dry-run : Show what would be done without executing}';

    /**
     * The console command description.
     */
    protected $description = 'Manage sync operations: retry failed syncs, cleanup old records, batch operations';

    protected SyncService $syncService;
    protected SyncStatusManager $statusManager;
    protected SyncAnalyticsService $analyticsService;

    public function __construct(
        SyncService $syncService,
        SyncStatusManager $statusManager,
        SyncAnalyticsService $analyticsService
    ) {
        parent::__construct();
        $this->syncService = $syncService;
        $this->statusManager = $statusManager;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'retry-failed' => $this->retryFailedSyncs(),
            'cleanup' => $this->cleanupOldRecords(),
            'batch-sync' => $this->batchSync(),
            'status' => $this->showStatus(),
            'analytics' => $this->showAnalytics(),
            default => $this->error("Unknown action: {$action}")
        };
    }

    /**
     * Retry failed sync operations
     */
    protected function retryFailedSyncs(): int
    {
        $hours = (int) $this->option('hours', 24);
        $limit = (int) $this->option('limit', 100);
        $connectionPairId = $this->option('connection-pair-id');
        $dryRun = $this->option('dry-run');
        
        $this->info("Finding failed syncs from the last {$hours} hours...");
        
        $query = ConnectionPairProduct::where('sync_status', 'failed')
            ->where('last_sync_attempt', '>=', Carbon::now()->subHours($hours));
            
        if ($connectionPairId) {
            $query->where('connection_pair_id', $connectionPairId);
        }
        
        $failedItems = $query->limit($limit)->get();
        
        if ($failedItems->isEmpty()) {
            $this->info('No failed items found to retry.');
            return 0;
        }
        
        $this->table(
            ['ID', 'Connection Pair', 'Product SKU', 'Last Attempt', 'Error'],
            $failedItems->map(function ($item) {
                return [
                    $item->id,
                    $item->connection_pair_id,
                    $item->sku ?? 'N/A',
                    $item->last_sync_attempt?->format('Y-m-d H:i:s') ?? 'Never',
                    substr($item->sync_error ?? 'Unknown error', 0, 50) . '...'
                ];
            })->toArray()
        );
        
        if ($dryRun) {
            $this->info("[DRY RUN] Would retry {$failedItems->count()} failed items.");
            return 0;
        }
        
        if (!$this->option('force') && !$this->confirm("Retry {$failedItems->count()} failed items?")) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        $retryCount = 0;
        $bar = $this->output->createProgressBar($failedItems->count());
        
        foreach ($failedItems as $item) {
            if ($this->statusManager->shouldRetryFailedSync($item)) {
                $this->statusManager->markPending($item);
                $retryCount++;
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Successfully marked {$retryCount} items for retry.");
        
        return 0;
    }

    /**
     * Cleanup old sync records
     */
    protected function cleanupOldRecords(): int
    {
        $days = (int) $this->option('hours', 24 * 30) / 24; // Convert to days, default 30 days
        $dryRun = $this->option('dry-run');
        
        $this->info("Cleaning up sync records older than {$days} days...");
        
        $oldRecordsCount = $this->statusManager->getOldSyncRecordsCount(Carbon::now()->subDays($days));
        
        if ($oldRecordsCount === 0) {
            $this->info('No old records found to cleanup.');
            return 0;
        }
        
        if ($dryRun) {
            $this->info("[DRY RUN] Would cleanup {$oldRecordsCount} old sync records.");
            return 0;
        }
        
        if (!$this->option('force') && !$this->confirm("Delete {$oldRecordsCount} old sync records?")) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        $deletedCount = $this->statusManager->cleanupOldSyncRecords(Carbon::now()->subDays($days));
        $this->info("Successfully deleted {$deletedCount} old sync records.");
        
        return 0;
    }

    /**
     * Perform batch sync operation
     */
    protected function batchSync(): int
    {
        $limit = (int) $this->option('limit', 100);
        $connectionPairId = $this->option('connection-pair-id');
        $dryRun = $this->option('dry-run');
        
        $this->info('Finding items that need syncing...');
        
        $query = ConnectionPairProduct::where('sync_status', 'pending')
            ->orWhere(function ($q) {
                $q->where('sync_status', 'failed')
                  ->where('last_sync_attempt', '<', Carbon::now()->subHours(1));
            });
            
        if ($connectionPairId) {
            $query->where('connection_pair_id', $connectionPairId);
        }
        
        $items = $query->limit($limit)->get();
        
        if ($items->isEmpty()) {
            $this->info('No items found that need syncing.');
            return 0;
        }
        
        $this->info("Found {$items->count()} items that need syncing.");
        
        if ($dryRun) {
            $this->info("[DRY RUN] Would dispatch batch sync job for {$items->count()} items.");
            return 0;
        }
        
        if (!$this->option('force') && !$this->confirm("Dispatch batch sync for {$items->count()} items?")) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        // Split into chunks and dispatch batch jobs
        $chunks = $items->pluck('id')->chunk(50);
        $jobCount = 0;
        
        foreach ($chunks as $chunk) {
            BatchSyncConnectionPairProductsJob::dispatch(
                $chunk,
                'catalog',
                ['priority' => 'normal']
            );
            $jobCount++;
        }
        
        $this->info("Successfully dispatched {$jobCount} batch sync jobs.");
        
        return 0;
    }

    /**
     * Show sync status overview
     */
    protected function showStatus(): int
    {
        $this->info('Sync Status Overview');
        $this->line('==================');
        
        // Status distribution
        $statusDistribution = $this->analyticsService->getStatusDistribution();
        $this->info('Sync Status Distribution:');
        foreach ($statusDistribution as $status => $count) {
            $this->line("  {$status}: {$count}");
        }
        
        $this->newLine();
        
        // Catalog status distribution
        $catalogDistribution = $this->analyticsService->getCatalogStatusDistribution();
        $this->info('Catalog Status Distribution:');
        foreach ($catalogDistribution as $status => $count) {
            $this->line("  {$status}: {$count}");
        }
        
        $this->newLine();
        
        // Items needing attention
        $attention = $this->analyticsService->getItemsNeedingAttention();
        $this->info('Items Needing Attention:');
        foreach ($attention as $type => $count) {
            $this->line("  {$type}: {$count}");
        }
        
        $this->newLine();
        
        // Queue health
        $queueHealth = $this->analyticsService->getQueueHealth();
        $this->info('Queue Health:');
        $this->line("  Pending jobs: {$queueHealth['pending_jobs']}");
        $this->line("  Failed jobs: {$queueHealth['failed_jobs']}");
        if ($queueHealth['oldest_pending']) {
            $this->line("  Oldest pending: {$queueHealth['oldest_pending']}");
        }
        
        return 0;
    }

    /**
     * Show detailed analytics
     */
    protected function showAnalytics(): int
    {
        $period = '24h';
        $metrics = $this->analyticsService->getPerformanceMetrics($period);
        
        $this->info("Sync Analytics (Last {$period})");
        $this->line('========================');
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Syncs', $metrics['total_syncs']],
                ['Successful Syncs', $metrics['successful_syncs']],
                ['Failed Syncs', $metrics['failed_syncs']],
                ['Pending Syncs', $metrics['pending_syncs']],
                ['Average Sync Time', round($metrics['average_sync_time'] ?? 0, 2) . 's'],
                ['Sync Rate', $metrics['sync_rate'] . '/hour'],
                ['Error Rate', $metrics['error_rate'] . '%']
            ]
        );
        
        if (!empty($metrics['top_errors'])) {
            $this->newLine();
            $this->info('Top Errors:');
            foreach ($metrics['top_errors'] as $error => $count) {
                $this->line("  {$count}x: " . substr($error, 0, 80) . '...');
            }
        }
        
        if (!empty($metrics['sync_by_destination'])) {
            $this->newLine();
            $this->info('Syncs by Destination:');
            foreach ($metrics['sync_by_destination'] as $destination => $count) {
                $this->line("  {$destination}: {$count}");
            }
        }
        
        return 0;
    }
}
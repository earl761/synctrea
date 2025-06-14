<?php

namespace App\Services;

use App\Models\ConnectionPairProduct;
use Illuminate\Support\Facades\Log;

class SyncStatusManager
{
    // Sync status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'synced';
    public const STATUS_FAILED = 'failed';

    // Catalog status constants
    public const CATALOG_STATUS_DEFAULT = 'default';
    public const CATALOG_STATUS_QUEUED = 'queued';
    public const CATALOG_STATUS_IN_CATALOG = 'in_catalog';
    public const CATALOG_STATUS_PENDING_DELETION = 'pending_deletion';
    public const CATALOG_STATUS_NOT_IN_CATALOG = 'not_in_catalog';
    public const CATALOG_STATUS_PENDING_CREATION = 'pending_creation';

    /**
     * Mark connection pair product as pending sync
     */
    public function markAsPending(ConnectionPairProduct $item, string $reason = null): void
    {
        $this->updateSyncStatus($item, self::STATUS_PENDING, $reason);
        
        $item->update([
            'last_sync_attempt' => now()
        ]);

        Log::info('Marked connection pair product as pending', [
            'connection_pair_product_id' => $item->id,
            'reason' => $reason
        ]);
    }

    /**
     * Mark connection pair product as in progress
     */
    public function markAsInProgress(ConnectionPairProduct $item, string $reason = null): void
    {
        $this->updateSyncStatus($item, self::STATUS_IN_PROGRESS, $reason);

        Log::info('Marked connection pair product as in progress', [
            'connection_pair_product_id' => $item->id,
            'reason' => $reason
        ]);
    }

    /**
     * Mark connection pair product as completed
     */
    public function markAsCompleted(ConnectionPairProduct $item, string $reason = null): void
    {
        $item->update([
            'sync_status' => self::STATUS_COMPLETED,
            'last_synced_at' => now(),
            'sync_error' => null // Clear any previous errors
        ]);

        Log::info('Marked connection pair product as completed', [
            'connection_pair_product_id' => $item->id,
            'reason' => $reason
        ]);
    }

    /**
     * Mark connection pair product as failed
     */
    public function markAsFailed(ConnectionPairProduct $item, string $error, string $reason = null): void
    {
        $item->update([
            'sync_status' => self::STATUS_FAILED,
            'sync_error' => $error
        ]);

        Log::error('Marked connection pair product as failed', [
            'connection_pair_product_id' => $item->id,
            'error' => $error,
            'reason' => $reason
        ]);
    }

    /**
     * Update catalog status
     */
    public function updateCatalogStatus(ConnectionPairProduct $item, string $catalogStatus, string $reason = null): void
    {
        $previousStatus = $item->catalog_status;
        
        $item->update([
            'catalog_status' => $catalogStatus
        ]);

        Log::info('Updated catalog status', [
            'connection_pair_product_id' => $item->id,
            'previous_status' => $previousStatus,
            'new_status' => $catalogStatus,
            'reason' => $reason
        ]);
    }

    /**
     * Check if item needs sync based on status and timing
     */
    public function needsSync(ConnectionPairProduct $item): bool
    {
        // Always sync if status is pending
        if ($item->sync_status === self::STATUS_PENDING) {
            return true;
        }

        // Don't sync if currently in progress
        if ($item->sync_status === self::STATUS_IN_PROGRESS) {
            return false;
        }

        // Check if enough time has passed since last sync attempt for failed items
        if ($item->sync_status === self::STATUS_FAILED) {
            return $this->shouldRetryFailedSync($item);
        }

        return false;
    }

    /**
     * Check if item has recent sync attempt (within last 5 minutes)
     */
    public function hasRecentSyncAttempt(ConnectionPairProduct $item): bool
    {
        if (!$item->last_sync_attempt) {
            return false;
        }

        return $item->last_sync_attempt->diffInMinutes(now()) < 5;
    }

    /**
     * Get sync statistics for monitoring
     */
    public function getSyncStatistics(): array
    {
        $stats = ConnectionPairProduct::selectRaw('
            sync_status,
            COUNT(*) as count,
            MAX(last_synced_at) as last_sync,
            MIN(last_sync_attempt) as oldest_attempt
        ')
        ->groupBy('sync_status')
        ->get()
        ->keyBy('sync_status')
        ->toArray();

        return [
            'pending' => $stats[self::STATUS_PENDING]['count'] ?? 0,
            'in_progress' => $stats[self::STATUS_IN_PROGRESS]['count'] ?? 0,
            'completed' => $stats[self::STATUS_COMPLETED]['count'] ?? 0,
            'failed' => $stats[self::STATUS_FAILED]['count'] ?? 0,
            'last_successful_sync' => $stats[self::STATUS_COMPLETED]['last_sync'] ?? null,
            'oldest_pending_attempt' => $stats[self::STATUS_PENDING]['oldest_attempt'] ?? null
        ];
    }

    /**
     * Reset failed items for retry
     */
    public function resetFailedItems(int $maxAge = 60): int
    {
        $cutoff = now()->subMinutes($maxAge);
        
        $count = ConnectionPairProduct::where('sync_status', self::STATUS_FAILED)
            ->where('last_sync_attempt', '<', $cutoff)
            ->update([
                'sync_status' => self::STATUS_PENDING,
                'sync_error' => null
            ]);

        Log::info('Reset failed sync items for retry', [
            'count' => $count,
            'max_age_minutes' => $maxAge
        ]);

        return $count;
    }

    /**
     * Clean up old completed sync records
     */
    public function cleanupOldSyncRecords(int $daysOld = 30): int
    {
        $cutoff = now()->subDays($daysOld);
        
        $count = ConnectionPairProduct::where('sync_status', self::STATUS_COMPLETED)
            ->where('last_synced_at', '<', $cutoff)
            ->whereNull('deleted_at')
            ->count();

        // Note: We're not actually deleting, just counting for now
        // In production, you might want to archive or actually clean up
        
        Log::info('Old sync records identified for cleanup', [
            'count' => $count,
            'days_old' => $daysOld
        ]);

        return $count;
    }

    /**
     * Get count of old sync records
     */
    public function getOldSyncRecordsCount(Carbon $before): int
    {
        return SyncLog::where('created_at', '<', $before)->count();
    }

    /**
     * Reset failed items to pending status
     */
    // public function resetFailedItems(Carbon $since, ?int $connectionPairId = null): int
    // {
    //     $query = ConnectionPairProduct::where('sync_status', self::SYNC_STATUS_FAILED)
    //         ->where('last_sync_attempt', '>=', $since);
            
    //     if ($connectionPairId) {
    //         $query->where('connection_pair_id', $connectionPairId);
    /**
     * Update sync status with optional error
     */
    public function updateSyncStatus(ConnectionPairProduct $item, string $status, string $reason = null): void
    {
        $updates = ['sync_status' => $status];
        
        // Clear error if moving to non-failed status
        if ($status !== self::STATUS_FAILED) {
            $updates['sync_error'] = null;
        }

        $item->update($updates);
    }

    /**
     * Determine if failed sync should be retried
     */
    private function shouldRetryFailedSync(ConnectionPairProduct $item): bool
    {
        if (!$item->last_sync_attempt) {
            return true;
        }

        // Exponential backoff: 5min, 15min, 45min, 2hr, 6hr, 24hr
        $minutesSinceLastAttempt = $item->last_sync_attempt->diffInMinutes(now());
        
        // Simple retry logic - can be enhanced with attempt counting
        return $minutesSinceLastAttempt >= 15;
    }
}
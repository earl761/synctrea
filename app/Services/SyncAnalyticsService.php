<?php

namespace App\Services;

use App\Models\ConnectionPairProduct;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SyncAnalyticsService
{
    /**
     * Get sync performance metrics for a given time period
     */
    public function getPerformanceMetrics(string $period = '24h'): array
    {
        $cacheKey = "sync_metrics_{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($period) {
            $startTime = $this->getStartTimeForPeriod($period);
            
            return [
                'total_syncs' => $this->getTotalSyncs($startTime),
                'successful_syncs' => $this->getSuccessfulSyncs($startTime),
                'failed_syncs' => $this->getFailedSyncs($startTime),
                'pending_syncs' => $this->getPendingSyncs(),
                'average_sync_time' => $this->getAverageSyncTime($startTime),
                'sync_rate' => $this->getSyncRate($startTime),
                'error_rate' => $this->getErrorRate($startTime),
                'top_errors' => $this->getTopErrors($startTime),
                'sync_by_destination' => $this->getSyncByDestination($startTime),
                'hourly_distribution' => $this->getHourlyDistribution($startTime)
            ];
        });
    }

    /**
     * Get sync status distribution
     */
    public function getStatusDistribution(): array
    {
        return Cache::remember('sync_status_distribution', 300, function () {
            return ConnectionPairProduct::select('sync_status', DB::raw('count(*) as count'))
                ->groupBy('sync_status')
                ->pluck('count', 'sync_status')
                ->toArray();
        });
    }

    /**
     * Get catalog status distribution
     */
    public function getCatalogStatusDistribution(): array
    {
        return Cache::remember('catalog_status_distribution', 300, function () {
            return ConnectionPairProduct::select('catalog_status', DB::raw('count(*) as count'))
                ->groupBy('catalog_status')
                ->pluck('count', 'catalog_status')
                ->toArray();
        });
    }

    /**
     * Get items that need attention (failed, stuck, etc.)
     */
    public function getItemsNeedingAttention(): array
    {
        $stuckThreshold = Carbon::now()->subHours(2);
        $failedRetryThreshold = Carbon::now()->subHours(24);
        
        // Get actual items that need attention with their details
        $stuckItems = ConnectionPairProduct::where('sync_status', 'in_progress')
            ->where('last_sync_attempt', '<', $stuckThreshold)
            ->with(['product', 'connectionPair'])
            ->get()
            ->map(function ($item) {
                return [
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'connection_pair_name' => $item->connectionPair->name ?? 'Unknown Connection',
                    'sync_status' => $item->sync_status,
                    'last_sync_attempt' => $item->last_sync_attempt,
                    'sync_error' => $item->sync_error
                ];
            });
            
        $failedItems = ConnectionPairProduct::where('sync_status', 'failed')
            ->with(['product', 'connectionPair'])
            ->limit(10) // Limit to avoid too many results
            ->get()
            ->map(function ($item) {
                return [
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'connection_pair_name' => $item->connectionPair->name ?? 'Unknown Connection',
                    'sync_status' => $item->sync_status,
                    'last_sync_attempt' => $item->last_sync_attempt,
                    'sync_error' => $item->sync_error
                ];
            });
            
        $pendingTooLong = ConnectionPairProduct::where('sync_status', 'pending')
            ->where('created_at', '<', Carbon::now()->subHours(6))
            ->with(['product', 'connectionPair'])
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'connection_pair_name' => $item->connectionPair->name ?? 'Unknown Connection',
                    'sync_status' => $item->sync_status,
                    'last_sync_attempt' => $item->last_sync_attempt,
                    'sync_error' => $item->sync_error
                ];
            });
        
        // Combine all items and return as array
        return $stuckItems->concat($failedItems)->concat($pendingTooLong)->toArray();
    }

    /**
     * Get sync trends over time
     */
    public function getSyncTrends(int $days = 7): array
    {
        $cacheKey = "sync_trends_{$days}d";
        
        return Cache::remember($cacheKey, 600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            
            $trends = [];
            
            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $nextDate = $date->copy()->addDay();
                
                $trends[] = [
                    'date' => $date->format('Y-m-d'),
                    'successful' => ConnectionPairProduct::where('sync_status', 'synced')
                        ->whereBetween('last_synced_at', [$date, $nextDate])
                        ->count(),
                    'failed' => SyncLog::where('status', 'failed')
                        ->whereBetween('created_at', [$date, $nextDate])
                        ->count(),
                    'total_attempts' => SyncLog::whereBetween('created_at', [$date, $nextDate])
                        ->count()
                ];
            }
            
            return $trends;
        });
    }

    /**
     * Get performance by connection pair
     */
    public function getPerformanceByConnectionPair(int $limit = 10): array
    {
        return Cache::remember('performance_by_connection_pair', 300, function () use ($limit) {
            $results = DB::table('connection_pair_product as cpp')
                ->join('connection_pairs as cp', 'cpp.connection_pair_id', '=', 'cp.id')
                ->join('companies as c', 'cp.company_id', '=', 'c.id')
                ->join('destinations as d', 'cp.destination_id', '=', 'd.id')
                ->select(
                    'cp.id as connection_pair_id',
                    'c.name as company_name',
                    'd.name as destination_name',
                    'd.type as destination_type',
                    DB::raw('COUNT(*) as total_items'),
                    DB::raw('SUM(CASE WHEN cpp.sync_status = "synced" THEN 1 ELSE 0 END) as synced_items'),
                    DB::raw('SUM(CASE WHEN cpp.sync_status = "failed" THEN 1 ELSE 0 END) as failed_items'),
                    DB::raw('SUM(CASE WHEN cpp.sync_status = "pending" THEN 1 ELSE 0 END) as pending_items'),
                    DB::raw('ROUND((SUM(CASE WHEN cpp.sync_status = "synced" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as success_rate')
                )
                ->groupBy('cp.id', 'c.name', 'd.name', 'd.type')
                ->orderBy('total_items', 'desc')
                ->limit($limit)
                ->get();

            // Convert stdClass objects to arrays
            return $results->map(function ($item) {
                return [
                    'name' => $item->company_name . ' → ' . $item->destination_name,
                    'total_syncs' => $item->total_items,
                    'success_rate' => $item->success_rate,
                    'avg_time' => 0, // This would need additional calculation if needed
                    'last_sync' => null, // This would need additional query if needed
                    'synced_items' => $item->synced_items,
                    'failed_items' => $item->failed_items,
                    'pending_items' => $item->pending_items,
                ];
            })->toArray();
        });
    }

    /**
     * Get sync queue health metrics
     */
    public function getQueueHealth(): array
    {
        return [
            'pending_jobs' => DB::table('jobs')->where('queue', 'like', '%sync%')->count(),
            'failed_jobs' => DB::table('failed_jobs')->where('payload', 'like', '%sync%')->count(),
            'oldest_pending' => DB::table('jobs')
                ->where('queue', 'like', '%sync%')
                ->orderBy('created_at')
                ->value('created_at'),
            'queue_sizes' => [
                'catalog-sync' => DB::table('jobs')->where('queue', 'catalog-sync')->count(),
                'sync-batch' => DB::table('jobs')->where('queue', 'sync-batch')->count(),
                'prestashop-sync' => DB::table('jobs')->where('queue', 'prestashop-sync')->count()
            ]
        ];
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): void
    {
        $patterns = [
            'sync_metrics_*',
            'sync_status_distribution',
            'catalog_status_distribution',
            'sync_trends_*',
            'performance_by_connection_pair'
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Helper methods
     */
    protected function getStartTimeForPeriod(string $period): Carbon
    {
        return match ($period) {
            '1h' => Carbon::now()->subHour(),
            '24h' => Carbon::now()->subDay(),
            '7d' => Carbon::now()->subWeek(),
            '30d' => Carbon::now()->subMonth(),
            default => Carbon::now()->subDay()
        };
    }

    public function getTotalSyncs(Carbon $startTime = null): int
    {
        $startTime = $startTime ?? Carbon::now()->subDay();
        return ConnectionPairProduct::where('last_sync_attempt', '>=', $startTime)->count();
    }

    public function getSuccessfulSyncs(Carbon $startTime = null): int
    {
        $startTime = $startTime ?? Carbon::now()->subDay();
        return ConnectionPairProduct::where('sync_status', 'synced')
            ->where('last_synced_at', '>=', $startTime)
            ->count();
    }

    public function getFailedSyncs(Carbon $startTime = null): int
    {
        $startTime = $startTime ?? Carbon::now()->subDay();
        return ConnectionPairProduct::where('sync_status', 'failed')
            ->where('last_sync_attempt', '>=', $startTime)
            ->count();
    }

    public function getPendingSyncs(): int
    {
        return ConnectionPairProduct::where('sync_status', 'pending')->count();
    }

    public function getAverageSyncTime(Carbon $startTime = null): ?float
    {
        $startTime = $startTime ?? Carbon::now()->subDay();
        return SyncLog::where('created_at', '>=', $startTime)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration')
            ->value('avg_duration');
    }

    public function getSyncRate(Carbon $startTime = null): float
    {
        $startTime = $startTime ?? Carbon::now()->subDay();
        $hours = Carbon::now()->diffInHours($startTime);
        $totalSyncs = $this->getTotalSyncs($startTime);
        
        return $hours > 0 ? round($totalSyncs / $hours, 2) : 0;
    }

    public function getErrorRate(Carbon $startTime = null): float
    {
        $startTime = $startTime ?? Carbon::now()->subDay();
        $total = $this->getTotalSyncs($startTime);
        $failed = $this->getFailedSyncs($startTime);
        
        return $total > 0 ? round(($failed / $total) * 100, 2) : 0;
    }

    public function getTopErrors(Carbon $startTime = null, int $limit = 5): array
    {
        $startTime = $startTime ?? Carbon::now()->subDay();
        return SyncLog::where('status', 'failed')
            ->where('created_at', '>=', $startTime)
            ->select('error_message', DB::raw('count(*) as count'))
            ->groupBy('error_message')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'error_message')
            ->toArray();
    }

    protected function getSyncByDestination(Carbon $startTime): array
    {
        return DB::table('connection_pair_product as cpp')
            ->join('connection_pairs as cp', 'cpp.connection_pair_id', '=', 'cp.id')
            ->join('destinations as d', 'cp.destination_id', '=', 'd.id')
            ->where('cpp.last_sync_attempt', '>=', $startTime)
            ->select('d.type', DB::raw('count(*) as count'))
            ->groupBy('d.type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get syncs grouped by destination type
     */
    public function getSyncsByDestination(Carbon $startTime = null): array
    {
        if ($startTime === null) {
            $startTime = Carbon::now()->subDay();
        }
        
        return $this->getSyncByDestination($startTime);
    }

    protected function getHourlyDistribution(Carbon $startTime): array
    {
        return ConnectionPairProduct::where('last_sync_attempt', '>=', $startTime)
            ->select(DB::raw('HOUR(last_sync_attempt) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }
}
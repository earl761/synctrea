<?php

namespace App\Http\Controllers;

use App\Jobs\BatchSyncConnectionPairProductsJob;
use App\Models\ConnectionPairProduct;
use App\Services\SyncAnalyticsService;
use App\Services\SyncStatusManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;

class SyncDashboardController extends Controller
{
    protected SyncAnalyticsService $analyticsService;
    protected SyncStatusManager $statusManager;

    public function __construct(
        SyncAnalyticsService $analyticsService,
        SyncStatusManager $statusManager
    ) {
        $this->analyticsService = $analyticsService;
        $this->statusManager = $statusManager;
    }

    /**
     * Display the sync dashboard
     */
    public function index(): View
    {
        $metrics = $this->analyticsService->getPerformanceMetrics('24h');
        $statusDistribution = $this->analyticsService->getStatusDistribution();
        $catalogDistribution = $this->analyticsService->getCatalogStatusDistribution();
        $attention = $this->analyticsService->getItemsNeedingAttention();
        $queueHealth = $this->analyticsService->getQueueHealth();
        $trends = $this->analyticsService->getSyncTrends(7);
        $performanceByPair = $this->analyticsService->getPerformanceByConnectionPair(10);

        return view('sync.dashboard', compact(
            'metrics',
            'statusDistribution',
            'catalogDistribution',
            'attention',
            'queueHealth',
            'trends',
            'performanceByPair'
        ));
    }

    /**
     * Get real-time metrics via AJAX
     */
    public function metrics(Request $request): JsonResponse
    {
        $period = $request->get('period', '24h');
        $metrics = $this->analyticsService->getPerformanceMetrics($period);
        
        return response()->json($metrics);
    }

    /**
     * Get sync trends data
     */
    public function trends(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 7);
        $trends = $this->analyticsService->getSyncTrends($days);
        
        return response()->json($trends);
    }

    /**
     * Get items that need attention
     */
    public function attention(): JsonResponse
    {
        $attention = $this->analyticsService->getItemsNeedingAttention();
        
        return response()->json($attention);
    }

    /**
     * Get queue health status
     */
    public function queueHealth(): JsonResponse
    {
        $health = $this->analyticsService->getQueueHealth();
        
        return response()->json($health);
    }

    /**
     * Get failed sync items with pagination
     */
    public function failedItems(Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);
        $hours = (int) $request->get('hours', 24);
        
        $query = ConnectionPairProduct::with(['connectionPair.company', 'connectionPair.destination', 'product'])
            ->where('sync_status', 'failed')
            ->where('last_sync_attempt', '>=', Carbon::now()->subHours($hours))
            ->orderBy('last_sync_attempt', 'desc');
            
        $items = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total()
            ]
        ]);
    }

    /**
     * Get pending sync items with pagination
     */
    public function pendingItems(Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);
        
        $query = ConnectionPairProduct::with(['connectionPair.company', 'connectionPair.destination', 'product'])
            ->where('sync_status', 'pending')
            ->orderBy('created_at', 'desc');
            
        $items = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total()
            ]
        ]);
    }

    /**
     * Retry failed sync items
     */
    public function retryFailed(Request $request): JsonResponse
    {
        $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:connection_pair_product,id'
        ]);
        
        $itemIds = $request->get('item_ids');
        $retryCount = 0;
        
        $items = ConnectionPairProduct::whereIn('id', $itemIds)
            ->where('sync_status', 'failed')
            ->get();
            
        foreach ($items as $item) {
            if ($this->statusManager->shouldRetryFailedSync($item)) {
                $this->statusManager->markPending($item);
                $retryCount++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Successfully marked {$retryCount} items for retry.",
            'retry_count' => $retryCount
        ]);
    }

    /**
     * Batch sync pending items
     */
    public function batchSync(Request $request): JsonResponse
    {
        $request->validate([
            'connection_pair_id' => 'nullable|integer|exists:connection_pairs,id',
            'limit' => 'nullable|integer|min:1|max:500'
        ]);
        
        $connectionPairId = $request->get('connection_pair_id');
        $limit = (int) $request->get('limit', 100);
        
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
            return response()->json([
                'success' => false,
                'message' => 'No items found that need syncing.'
            ]);
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
        
        return response()->json([
            'success' => true,
            'message' => "Successfully dispatched {$jobCount} batch sync jobs for {$items->count()} items.",
            'job_count' => $jobCount,
            'item_count' => $items->count()
        ]);
    }

    /**
     * Reset failed items (mark as pending)
     */
    public function resetFailed(Request $request): JsonResponse
    {
        $request->validate([
            'hours' => 'nullable|integer|min:1|max:168', // Max 1 week
            'connection_pair_id' => 'nullable|integer|exists:connection_pairs,id'
        ]);
        
        $hours = (int) $request->get('hours', 24);
        $connectionPairId = $request->get('connection_pair_id');
        
        $resetCount = $this->statusManager->resetFailedItems(
            Carbon::now()->subHours($hours),
            $connectionPairId
        );
        
        return response()->json([
            'success' => true,
            'message' => "Successfully reset {$resetCount} failed items to pending status.",
            'reset_count' => $resetCount
        ]);
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): JsonResponse
    {
        $this->analyticsService->clearCache();
        
        return response()->json([
            'success' => true,
            'message' => 'Analytics cache cleared successfully.'
        ]);
    }

    /**
     * Export sync data as CSV
     */
    public function export(Request $request)
    {
        $request->validate([
            'type' => 'required|in:failed,pending,all',
            'hours' => 'nullable|integer|min:1|max:720' // Max 30 days
        ]);
        
        $type = $request->get('type');
        $hours = (int) $request->get('hours', 24);
        
        $query = ConnectionPairProduct::with(['connectionPair.company', 'connectionPair.destination', 'product']);
        
        switch ($type) {
            case 'failed':
                $query->where('sync_status', 'failed')
                      ->where('last_sync_attempt', '>=', Carbon::now()->subHours($hours));
                break;
            case 'pending':
                $query->where('sync_status', 'pending');
                break;
            case 'all':
                $query->where('last_sync_attempt', '>=', Carbon::now()->subHours($hours));
                break;
        }
        
        $items = $query->get();
        
        $filename = "sync_export_{$type}_" . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}"
        ];
        
        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'Connection Pair ID',
                'Company',
                'Destination',
                'Product SKU',
                'Product Name',
                'Sync Status',
                'Catalog Status',
                'Last Sync Attempt',
                'Last Synced At',
                'Error Message'
            ]);
            
            foreach ($items as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->connection_pair_id,
                    $item->connectionPair->company->name ?? 'N/A',
                    $item->connectionPair->destination->name ?? 'N/A',
                    $item->sku ?? 'N/A',
                    $item->name ?? $item->product->name ?? 'N/A',
                    $item->sync_status,
                    $item->catalog_status,
                    $item->last_sync_attempt?->format('Y-m-d H:i:s') ?? 'Never',
                    $item->last_synced_at?->format('Y-m-d H:i:s') ?? 'Never',
                    $item->sync_error ?? ''
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
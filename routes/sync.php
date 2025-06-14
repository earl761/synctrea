<?php

use App\Http\Controllers\SyncDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sync Dashboard Routes
|--------------------------------------------------------------------------
|
| These routes handle the sync dashboard and related API endpoints for
| monitoring and managing sync operations.
|
*/

// Sync Dashboard Routes
Route::prefix('sync')->name('sync.')->middleware(['auth', 'verified'])->group(function () {
    
    // Main dashboard
    Route::get('/dashboard', [SyncDashboardController::class, 'index'])->name('dashboard');
    
    // API endpoints for real-time data
    Route::prefix('api')->name('api.')->group(function () {
        
        // Metrics and analytics
        Route::get('/metrics', [SyncDashboardController::class, 'metrics'])->name('metrics');
        Route::get('/trends', [SyncDashboardController::class, 'trends'])->name('trends');
        Route::get('/attention', [SyncDashboardController::class, 'attention'])->name('attention');
        Route::get('/queue-health', [SyncDashboardController::class, 'queueHealth'])->name('queue-health');
        
        // Item listings
        Route::get('/failed-items', [SyncDashboardController::class, 'failedItems'])->name('failed-items');
        Route::get('/pending-items', [SyncDashboardController::class, 'pendingItems'])->name('pending-items');
        
        // Actions
        Route::post('/retry-failed', [SyncDashboardController::class, 'retryFailed'])->name('retry-failed');
        Route::post('/batch-sync', [SyncDashboardController::class, 'batchSync'])->name('batch-sync');
        Route::post('/reset-failed', [SyncDashboardController::class, 'resetFailed'])->name('reset-failed');
        Route::post('/clear-cache', [SyncDashboardController::class, 'clearCache'])->name('clear-cache');
        
        // Export
        Route::get('/export', [SyncDashboardController::class, 'export'])->name('export');
    });
});
<?php

namespace App\Filament\Pages;

use App\Services\SyncAnalyticsService;
use App\Services\SyncService;
use App\Services\SyncStatusManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SyncDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Sync Dashboard';
    protected static ?string $title = 'Sync Management Dashboard';
    protected static string $view = 'filament.pages.sync-dashboard';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 100;

    public static function canAccess(): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        if (!static::canAccess()) {
            abort(403);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('batchSync')
                ->label('Batch Sync')
                ->icon('heroicon-o-play')
                ->color('success')
                ->form([
                    Select::make('connection_pair_id')
                        ->label('Connection Pair')
                        ->options(\App\Models\ConnectionPair::with(['supplier', 'destination'])
                            ->get()
                            ->mapWithKeys(function ($pair) {
                                return [$pair->id => $pair->supplier->name . ' → ' . $pair->destination->name];
                            }))
                        ->placeholder('All Connection Pairs'),
                    TextInput::make('chunk_size')
                        ->label('Chunk Size')
                        ->numeric()
                        ->default(100)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $syncService = app(SyncService::class);
                    $count = $syncService->performBatchSync(
                        $data['connection_pair_id'] ?? null,
                        $data['chunk_size'] ?? 100
                    );
                    
                    Notification::make()
                        ->title('Batch sync initiated')
                        ->body("Queued {$count} items for synchronization")
                        ->success()
                        ->send();
                }),

            Action::make('retryFailed')
                ->label('Retry Failed')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Select::make('connection_pair_id')
                        ->label('Connection Pair')
                        ->options(\App\Models\ConnectionPair::with(['supplier', 'destination'])
                            ->get()
                            ->mapWithKeys(function ($pair) {
                                return [$pair->id => $pair->supplier->name . ' → ' . $pair->destination->name];
                            }))
                        ->placeholder('All Connection Pairs'),
                ])
                ->action(function (array $data) {
                    $syncService = app(SyncService::class);
                    $count = $syncService->retryFailedSyncs($data['connection_pair_id'] ?? null);
                    
                    Notification::make()
                        ->title('Failed syncs retried')
                        ->body("Retried {$count} failed sync items")
                        ->success()
                        ->send();
                }),

            Action::make('resetFailed')
                ->label('Reset Failed')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Select::make('connection_pair_id')
                        ->label('Connection Pair')
                        ->options(\App\Models\ConnectionPair::with(['supplier', 'destination'])
                            ->get()
                            ->mapWithKeys(function ($pair) {
                                return [$pair->id => $pair->supplier->name . ' → ' . $pair->destination->name];
                            }))
                        ->placeholder('All Connection Pairs'),
                ])
                ->action(function (array $data) {
                    $syncStatusManager = app(SyncStatusManager::class);
                    $count = $syncStatusManager->resetFailedItems(
                        now()->subDay(),
                        $data['connection_pair_id'] ?? null
                    );
                    
                    Notification::make()
                        ->title('Failed items reset')
                        ->body("Reset {$count} failed items to pending")
                        ->success()
                        ->send();
                }),

            Action::make('clearCache')
                ->label('Clear Cache')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->action(function () {
                    Cache::tags(['sync_analytics'])->flush();
                    
                    Notification::make()
                        ->title('Cache cleared')
                        ->body('Sync analytics cache has been cleared')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getMetrics(): array
    {
        $analytics = app(SyncAnalyticsService::class);
        
        return [
            'total_syncs' => $analytics->getTotalSyncs(),
            'successful_syncs' => $analytics->getSuccessfulSyncs(),
            'failed_syncs' => $analytics->getFailedSyncs(),
            'pending_syncs' => $analytics->getPendingSyncs(),
            'sync_rate' => $analytics->getSyncRate(),
            'error_rate' => $analytics->getErrorRate(),
            'average_sync_time' => $analytics->getAverageSyncTime(),
        ];
    }

    public function getStatusDistribution(): array
    {
        return app(SyncAnalyticsService::class)->getStatusDistribution();
    }

    public function getTopErrors(): array
    {
        return app(SyncAnalyticsService::class)->getTopErrors();
    }

    public function getSyncsByDestination(): array
    {
        return app(SyncAnalyticsService::class)->getSyncsByDestination();
    }

    public function getItemsNeedingAttention(): array
    {
        return app(SyncAnalyticsService::class)->getItemsNeedingAttention();
    }

    public function getQueueHealth(): array
    {
        return app(SyncAnalyticsService::class)->getQueueHealth();
    }

    public function getPerformanceByConnectionPair(): array
    {
        return app(SyncAnalyticsService::class)->getPerformanceByConnectionPair();
    }
}
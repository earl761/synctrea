<?php

namespace App\Filament\Resources\ConnectionPairProductResource\Pages;

use App\Filament\Resources\ConnectionPairProductResource;
use App\Models\ConnectionPairProduct;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Components\Tab;
use Filament\Support\Enums\IconPosition;
use Livewire\Attributes\Url;

class ListConnectionPairProducts extends ListRecords
{
    protected static string $resource = ConnectionPairProductResource::class;

    public bool $isTableReordering = false;
    
    #[Url(as: 'connection_pair_id')]
    public ?string $connection_pair_id = null;

    #[Url(as: 'activeTab', except: 'default')]
    public ?string $activeTab = null;

    public function getDefaultActiveTab(): string
    {
        return 'default';
    }

    public function mount(): void
    {
        parent::mount();
        $this->connection_pair_id = request()->query('connection_pair_id');
        $this->activeTab = request()->query('activeTab', $this->getDefaultActiveTab());
    }

    public function getTabs(): array
    {
        if (!$this->connection_pair_id) {
            return [];
        }

        $baseQuery = ConnectionPairProduct::query()
            ->where('connection_pair_id', $this->connection_pair_id);

        return [
            'default' => Tab::make('Default')
                ->badge($baseQuery->clone()->where('catalog_status', ConnectionPairProduct::STATUS_DEFAULT)->count())
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('catalog_status', ConnectionPairProduct::STATUS_DEFAULT);
                })
                ->icon('heroicon-o-queue-list'),
            'queued' => Tab::make('Queued')
                ->badge($baseQuery->clone()->where('catalog_status', ConnectionPairProduct::STATUS_QUEUED)->count())
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('catalog_status', ConnectionPairProduct::STATUS_QUEUED);
                })
                ->icon('heroicon-o-clock'),
            'in_catalog' => Tab::make('In Catalog')
                ->badge($baseQuery->clone()->where('catalog_status', ConnectionPairProduct::STATUS_IN_CATALOG)->count())
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('catalog_status', ConnectionPairProduct::STATUS_IN_CATALOG);
                })
                ->icon('heroicon-o-check-circle'),
        ];
    }

    public function updatedActiveTab(): void
    {
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        $query = ConnectionPairProduct::query()
            ->when($this->connection_pair_id, function ($query) {
                return $query->where('connection_pair_id', $this->connection_pair_id);
            });

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Add Single Product')
                ->url(fn () => $this->getResource()::getUrl('create', [
                    'connection_pair_id' => $this->connection_pair_id
                ])),
            \Filament\Actions\Action::make('on_demand_sync')
                ->label('On-Demand Sync')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $connectionPairId = $this->connection_pair_id;
                    
                    if (!$connectionPairId) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Sync Failed')
                            ->body('Connection pair ID not found.')
                            ->send();
                        return;
                    }
                    
                    // Get the connection pair to check destination
                    $connectionPair = \App\Models\ConnectionPair::find($connectionPairId);
                    if (!$connectionPair) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Sync Failed')
                            ->body('Connection pair not found.')
                            ->send();
                        return;
                    }
                    
                    // Check if destination is Amazon
                    if (strtolower($connectionPair->destination->name) === 'amazon') {
                        try {
                            \Illuminate\Support\Facades\Artisan::call('amazon:bulk-catalog-update', [
                                'connectionPairId' => $connectionPairId
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Sync Initiated')
                                ->body('Amazon bulk catalog update has been started for this connection pair.')
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Sync Failed')
                                ->body('Failed to start Amazon sync: ' . $e->getMessage())
                                ->send();
                        }
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Sync Not Available')
                            ->body('On-demand sync is currently only available for Amazon destinations.')
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Confirm On-Demand Sync')
                ->modalDescription('This will trigger a bulk catalog update for all products in this connection pair. Are you sure you want to proceed?')
                ->modalSubmitActionLabel('Start Sync'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}
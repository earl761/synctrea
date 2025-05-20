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
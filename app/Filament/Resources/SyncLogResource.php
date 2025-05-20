<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SyncLogResource\Pages;
use App\Models\SyncLog;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static ?string $navigationIcon = 'fluentui-stack-20';
    protected static ?string $navigationGroup = 'Integrations';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Card::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('product_id')
                                ->relationship('product', 'sku')
                                ->required(),
                            Select::make('status')
                                ->options([
                                    'success' => 'Success',
                                    'failed' => 'Failed',
                                    'pending' => 'Pending',
                                ])
                                ->required(),
                            TextInput::make('sync_type')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('message')
                                ->maxLength(65535)
                                ->columnSpan(2),
                        ]),
                ])
        
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('product.sku')
                ->searchable()
                ->sortable(),
            TextColumn::make('product.name')
                ->searchable()
                ->sortable(),
            TextColumn::make('sync_type')
                ->searchable()
                ->sortable(),
            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'success' => 'success',
                    'failed' => 'danger',
                    'pending' => 'warning',
                    'running' => 'info',
                })
                ->searchable()
                ->sortable(),
            TextColumn::make('message')
                ->limit(50)
                ->searchable(),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyncLogs::route('/'),
            'create' => Pages\CreateSyncLog::route('/create'),
            'edit' => Pages\EditSyncLog::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('product')
                ->relationship('product', 'sku'),
            SelectFilter::make('status')
                ->options([
                    'success' => 'Success',
                    'failed' => 'Failed',
                    'pending' => 'Pending',
                    'running' => 'Running',
                ]),
            SelectFilter::make('sync_type')
                ->options([
                    'inventory' => 'Inventory',
                    'price' => 'Price',
                    'product' => 'Product',
                ]),
        ];
    }
}
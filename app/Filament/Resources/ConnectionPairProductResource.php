<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConnectionPairProductResource\Pages;
use App\Models\ConnectionPairProduct;
use App\Models\Product;
use App\Models\ConnectionPair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class ConnectionPairProductResource extends Resource
{
    protected static ?string $model = ConnectionPairProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Integrations';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Connection Products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('connection_pair_id')
                    ->relationship('connectionPair', 'id')
                    ->required()
                    ->default(fn () => request()->query('connection_pair_id')),
                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('upc')
                    ->label('UPC')
                    ->maxLength(255),
                Forms\Components\Select::make('condition')
                    ->options([
                        'new' => 'New',
                        'used' => 'Used',
                        'refurbished' => 'Refurbished'
                    ])
                    ->default('new'),
                Forms\Components\TextInput::make('part_number')
                    ->label('Part Number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('final_price')
                    ->label('List Price')
                    ->disabled()
                    ->dehydrated(false)
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('weight')
                    ->numeric()
                    ->step(0.01)
                    ->suffix('kg'),
                Forms\Components\Select::make('catalog_status')
                    ->options([
                        ConnectionPairProduct::STATUS_DEFAULT => 'Default',
                        ConnectionPairProduct::STATUS_QUEUED => 'Queued',
                        ConnectionPairProduct::STATUS_IN_CATALOG => 'In Catalog',
                    ])
                    ->default(ConnectionPairProduct::STATUS_DEFAULT)
                    ->required(),
                Forms\Components\Select::make('price_override_type')
                    ->options([
                        'none' => 'No Override',
                        'fixed' => 'Fixed Price',
                        'percentage' => 'Percentage Markup'
                    ])
                    ->default('none')
                    ->reactive(),
                Forms\Components\TextInput::make('price_override')
                    ->numeric()
                    ->prefix('$')
                    ->visible(fn (callable $get) => $get('price_override_type') !== 'none'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->description(function($livewire) {
                $connectionPairId = request()->query('connection_pair_id');
                if (!$connectionPairId) return null;
                
                $connectionPair = \App\Models\ConnectionPair::with(['supplier', 'destination'])->find($connectionPairId);
                if (!$connectionPair) return null;

                return new \Illuminate\Support\HtmlString(view('filament.components.connection-pair-header', [
                    'supplier' => $connectionPair->supplier->name,
                    'destination' => $connectionPair->destination->name,
                ])->render());
            })
            ->headerActions([
                
                \Filament\Tables\Actions\Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(storage_path('app/public/connection_pair_products_template.csv'))
                    ->openUrlInNewTab(),
                \Filament\Tables\Actions\Action::make('import')
                    ->label('Import from CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('csv')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv'])
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $connectionPairId = request()->query('connection_pair_id');
                        if (!$connectionPairId) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Connection pair ID is required.')
                                ->send();
                            return;
                        }

                        // Store the uploaded file
                        $path = storage_path('app/public/' . $data['csv']);

                        // Run the import command
                        $command = new \App\Console\Commands\ImportConnectionPairProducts(app());
                        $exitCode = $command->handle([
                            'connection_pair_id' => $connectionPairId,
                            'csv' => $path,
                        ]);

                        if ($exitCode === 0) {
                            Notification::make()
                                ->success()
                                ->title('Import Completed')
                                ->body('Products have been imported successfully.')
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Import Failed')
                                ->body('Failed to import products. Check the logs for details.')
                                ->send();
                        }

                        // Clean up the uploaded file
                        if (file_exists($path)) {
                            unlink($path);
                        }
                    })
            ])
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('upc')
                    ->label('UPC')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('condition')
                    ->badge()
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'used',
                        'danger' => 'refurbished',
                    ])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('part_number')
                    ->label('Part Number')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('List Price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->numeric()
                    ->suffix(' kg')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('catalog_status')
                    ->colors([
                        'warning' => ConnectionPairProduct::STATUS_DEFAULT,
                        'primary' => ConnectionPairProduct::STATUS_QUEUED,
                        'success' => ConnectionPairProduct::STATUS_IN_CATALOG,
                    ]),
                Tables\Columns\TextColumn::make('price_override')
                    ->money()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\BadgeColumn::make('price_override_type')
                    ->colors([
                        'danger' => 'none',
                        'warning' => 'fixed',
                        'success' => 'percentage',
                    ])
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filtersFormColumns(3)
            ->filters([
                SelectFilter::make('catalog_status')
                    ->options([
                        ConnectionPairProduct::STATUS_DEFAULT => 'Default',
                        ConnectionPairProduct::STATUS_QUEUED => 'Queued',
                        ConnectionPairProduct::STATUS_IN_CATALOG => 'In Catalog',
                    ]),
                SelectFilter::make('price_override_type')
                    ->options([
                        'none' => 'No Override',
                        'fixed' => 'Fixed Price',
                        'percentage' => 'Percentage Markup'
                    ]),
                Tables\Filters\SelectFilter::make('has_upc')
                    ->label('UPC Status')
                    ->options([
                        '1' => 'Has UPC',
                        '0' => 'No UPC'
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'] !== null, function ($query) use ($data) {
                            return $data['value'] ? $query->whereNotNull('upc') : $query->whereNull('upc');
                        });
                    }),
                Tables\Filters\Filter::make('stock')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('stock_from')
                                    ->numeric()
                                    ->label('Min Quantity'),
                                Forms\Components\TextInput::make('stock_to')
                                    ->numeric()
                                    ->label('Max Quantity'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['stock_from'],
                                fn (Builder $query, $value): Builder => $query->where('stock', '>=', $value),
                            )
                            ->when(
                                $data['stock_to'],
                                fn (Builder $query, $value): Builder => $query->where('stock', '<=', $value),
                            );
                    }),
                Tables\Filters\Filter::make('weight')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('weight_from')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kg')
                                    ->label('Min Weight'),
                                Forms\Components\TextInput::make('weight_to')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kg')
                                    ->label('Max Weight'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['weight_from'],
                                fn (Builder $query, $value): Builder => $query->where('weight', '>=', $value),
                            )
                            ->when(
                                $data['weight_to'],
                                fn (Builder $query, $value): Builder => $query->where('weight', '<=', $value),
                            );
                    }),
                Tables\Filters\Filter::make('price')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_from')
                                    ->numeric()
                                    ->prefix('$')
                                    ->label('Min Price'),
                                Forms\Components\TextInput::make('price_to')
                                    ->numeric()
                                    ->prefix('$')
                                    ->label('Max Price'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $value): Builder => $query->where('price', '>=', $value),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $value): Builder => $query->where('price', '<=', $value),
                            );
                    }),
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('product', 'brand')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('queue')
                    ->action(fn (ConnectionPairProduct $record) => $record->update(['catalog_status' => ConnectionPairProduct::STATUS_QUEUED]))
                    ->requiresConfirmation()
                    ->visible(fn (ConnectionPairProduct $record) => $record->catalog_status === ConnectionPairProduct::STATUS_DEFAULT)
                    ->color('primary')
                    ->icon('heroicon-o-queue-list'),
                Tables\Actions\Action::make('move_to_catalog')
                    ->action(fn (ConnectionPairProduct $record) => $record->update(['catalog_status' => ConnectionPairProduct::STATUS_IN_CATALOG]))
                    ->requiresConfirmation()
                    ->visible(fn (ConnectionPairProduct $record) => $record->catalog_status === ConnectionPairProduct::STATUS_QUEUED)
                    ->color('success')
                    ->icon('heroicon-o-check'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('queue_selected')
                        ->action(fn (Collection $records) => $records->each->update(['catalog_status' => ConnectionPairProduct::STATUS_QUEUED]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->label('Queue Selected')
                        ->color('primary')
                        ->icon('heroicon-o-queue-list'),
                    Tables\Actions\BulkAction::make('move_to_catalog')
                        ->action(fn (Collection $records) => $records->each->update(['catalog_status' => ConnectionPairProduct::STATUS_IN_CATALOG]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->label('Move to Catalog')
                        ->color('success')
                        ->icon('heroicon-o-check'),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConnectionPairProducts::route('/'),
            'create' => Pages\CreateConnectionPairProduct::route('/create'),
            'edit' => Pages\EditConnectionPairProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['connectionPair.supplier', 'connectionPair.destination', 'product']);

        $connectionPairId = request()->query('connection_pair_id');

        // If not in query, try to get it from the route parameter (record)
        if (!$connectionPairId && request()->route('record')) {
            $recordId = request()->route('record');
            $record = \App\Models\ConnectionPairProduct::find($recordId);
            if ($record) {
                $connectionPairId = $record->connection_pair_id;
            }
        }

        // Only show notification and abort if still not set
        if (!$connectionPairId) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Missing Connection Pair')
                ->body('No connection pair was specified. Please access products from a connection pair context.')
                ->send();
            abort(403, 'No connection pair specified.');
        }

        return $query->where('connection_pair_id', $connectionPairId);
    }
}
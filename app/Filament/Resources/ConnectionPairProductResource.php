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
use Filament\Tables\Actions\Action;
use App\Models\PricingRule;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;

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
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('id', 'desc')
            ->striped()
            ->extremePaginationLinks()
            ->description(function($livewire) {
                $connectionPairId = request()->query('connection_pair_id');
                if (!$connectionPairId) return null;
                
                // Use cache to avoid repeated queries
                $cacheKey = "connection_pair_header_{$connectionPairId}";
                $headerData = cache()->remember($cacheKey, 300, function () use ($connectionPairId) {
                    return \App\Models\ConnectionPair::query()
                        ->select('id', 'supplier_id', 'destination_id')
                        ->with([
                            'supplier:id,name',
                            'destination:id,name'
                        ])
                        ->find($connectionPairId);
                });
                
                if (!$headerData) return null;

                return new \Illuminate\Support\HtmlString(view('filament.components.connection-pair-header', [
                    'supplier' => $headerData->supplier->name,
                    'destination' => $headerData->destination->name,
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
                    ->options(function () {
                        $connectionPairId = request()->query('connection_pair_id');
                        if (!$connectionPairId) return [];
                        
                        return \App\Models\ConnectionPairProduct::query()
                            ->leftJoin('products', 'connection_pair_product.product_id', '=', 'products.id')
                            ->where('connection_pair_product.connection_pair_id', $connectionPairId)
                            ->whereNotNull('products.brand')
                            ->distinct()
                            ->pluck('products.brand', 'products.brand')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], function ($query, $brand) {
                            return $query->where('products.brand', $brand);
                        });
                    })
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('move_to_catalog')
                    ->action(fn (ConnectionPairProduct $record) => $record->update(['catalog_status' => ConnectionPairProduct::STATUS_IN_CATALOG]))
                    ->requiresConfirmation()
                    ->visible(fn (ConnectionPairProduct $record) => $record->catalog_status === ConnectionPairProduct::STATUS_QUEUED)
                    ->color('success')
                    ->icon('heroicon-o-check'),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('queue')
                        ->action(fn (ConnectionPairProduct $record) => $record->update(['catalog_status' => ConnectionPairProduct::STATUS_QUEUED]))
                        ->requiresConfirmation()
                        ->visible(fn (ConnectionPairProduct $record) => $record->catalog_status === ConnectionPairProduct::STATUS_DEFAULT)
                        ->color('primary')
                        ->icon('heroicon-o-queue-list'),
                    Tables\Actions\Action::make('send_to_catalog')
                        ->action(function (ConnectionPairProduct $record) {
                            $record->update(['catalog_status' => ConnectionPairProduct::STATUS_IN_CATALOG]);
                            
                            Notification::make()
                                ->success()
                                ->title('Product Sent to Catalog')
                                ->body('Product has been sent to catalog successfully.')
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(fn (ConnectionPairProduct $record) => $record->catalog_status === ConnectionPairProduct::STATUS_QUEUED)
                        ->label('Send to Catalog')
                        ->color('info')
                        ->icon('heroicon-o-paper-airplane'),
                    Tables\Actions\EditAction::make(),
                    Action::make('create_pricing_rule')
                    ->label('Create Pricing Rule')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Section::make('Create Pricing Rule')
                            ->description('Create a pricing rule for this connection pair')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Rule Name')
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('rule_type')
                                            ->label('Rule Type')
                                            ->options([
                                                'percentage' => 'Percentage',
                                                'fixed_amount' => 'Fixed Amount',
                                                'fixed_price' => 'Fixed Price',
                                            ])
                                            ->required()
                                            ->reactive(),
                                    ]),
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('percentage_value')
                                            ->label('Percentage (%)')
                                            ->numeric()
                                            ->suffix('%')
                                            ->visible(fn (callable $get) => $get('rule_type') === 'percentage'),
                                        TextInput::make('amount_value')
                                            ->label('Amount')
                                            ->numeric()
                                            ->prefix('$')
                                            ->visible(fn (callable $get) => $get('rule_type') === 'fixed_amount'),
                                        TextInput::make('value')
                                            ->label('Fixed Price')
                                            ->numeric()
                                            ->prefix('$')
                                            ->visible(fn (callable $get) => $get('rule_type') === 'fixed_price'),
                                    ]),
                                TextInput::make('priority')
                                    ->label('Priority')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Higher numbers have higher priority'),
                            ])
                    ])
                    ->action(function (array $data, $record) {
                        try {
                            PricingRule::create([
                                'company_id' => $record->connectionPair->company_id,
                                'name' => $data['name'],
                                'type' => PricingRule::TYPE_GLOBAL_CONNECTION,
                                'supplier_id' => $record->connectionPair->supplier_id,
                                'destination_id' => $record->connectionPair->destination_id,
                                'rule_type' => $data['rule_type'],
                                'value' => $data['value'] ?? null,
                                'percentage_value' => $data['percentage_value'] ?? null,
                                'amount_value' => $data['amount_value'] ?? null,
                                'priority' => $data['priority'] ?? 0,
                                'is_active' => true,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Pricing rule created')
                                ->body('The pricing rule has been created and will be applied to products in this connection pair.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to create pricing rule')
                                ->body('There was an error creating the pricing rule: ' . $e->getMessage())
                                ->send();
                        }
                    }),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('queue_selected')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->toArray();
                            ConnectionPairProduct::whereIn('id', $ids)
                                ->update(['catalog_status' => ConnectionPairProduct::STATUS_QUEUED]);
                            
                            Notification::make()
                                ->success()
                                ->title('Products Queued')
                                ->body(count($ids) . ' products have been queued successfully.')
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->label('Queue Selected')
                        ->color('primary')
                        ->icon('heroicon-o-queue-list'),
                    Tables\Actions\BulkAction::make('move_to_catalog')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->toArray();
                            ConnectionPairProduct::whereIn('id', $ids)
                                ->update(['catalog_status' => ConnectionPairProduct::STATUS_IN_CATALOG]);
                            
                            Notification::make()
                                ->success()
                                ->title('Products Moved to Catalog')
                                ->body(count($ids) . ' products have been moved to catalog successfully.')
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->label('Move to Catalog')
                        ->color('success')
                        ->icon('heroicon-o-check'),

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
        $connectionPairId = request()->query('connection_pair_id');

        // If not in query, try to get it from the route parameter (record)
        if (!$connectionPairId && request()->route('record')) {
            $recordId = request()->route('record');
            $record = \App\Models\ConnectionPairProduct::select('connection_pair_id')
                ->where('connection_pair_product.id', $recordId)
                ->first();
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

        return parent::getEloquentQuery()
            ->where('connection_pair_product.connection_pair_id', $connectionPairId)
            ->with([
                'product:id,name,brand',
                'connectionPair:id,supplier_id,destination_id,company_id',
                'connectionPair.supplier:id,name',
                'connectionPair.destination:id,name'
            ]);
    }

    /**
     * Get the product name for display in the table
     */
    public static function getProductNameColumn()
    {
        return \Filament\Tables\Columns\TextColumn::make('product.name')
            ->label('Product Name')
            ->searchable()
            ->sortable();
    }

    /**
     * Get the product brand for display in the table
     */
    public static function getProductBrandColumn()
    {
        return \Filament\Tables\Columns\TextColumn::make('product.brand')
            ->label('Brand')
            ->searchable()
            ->sortable();
    }
}
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConnectionPairProductResource\Pages;
use App\Models\ConnectionPair;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;


class ConnectionPairProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Catalog Management';
    protected static ?string $navigationLabel = 'Connection Pair Products';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $connectionPairId = request()->get('tableFilters')['connection_pair'] ?? null;

        if ($connectionPairId) {
            $connectionPair = ConnectionPair::find($connectionPairId);
            return parent::getEloquentQuery()
                ->where('supplier_id', $connectionPair->supplier_id);
        }

        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->disabled(),
                    
                TextInput::make('sku')
                    ->required()
                    ->disabled(),

                Select::make('catalog_status')
                    ->options([
                        'default' => 'Default',
                        'queue' => 'Queue',
                        'catalog' => 'Catalog'
                    ])
                    ->required(),

                Select::make('price_override_type')
                    ->label('Price Override Type')
                    ->options([
                        'none' => 'No Override',
                        'flat' => 'Flat Amount',
                        'percentage' => 'Percentage Markup',
                        'tiered' => 'Tiered Pricing'
                    ])
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('price_override', null))
                    ->default('none'),

                TextInput::make('price_override')
                    ->label('Price Override')
                    ->numeric()
                    ->prefix(fn (callable $get) => $get('price_override_type') === 'percentage' ? '%' : '$')
                    ->suffix(fn (callable $get) => $get('price_override_type') === 'percentage' ? ' markup' : '')
                    ->hint('Enter override value based on selected type')
                    ->placeholder(fn (callable $get) => match ($get('price_override_type')) {
                        'flat' => 'Enter fixed price',
                        'percentage' => 'Enter markup percentage',
                        default => 'No override'
                    })
                    ->hidden(fn (callable $get) => $get('price_override_type') === 'none')
                    ->disabled(fn (callable $get) => $get('price_override_type') === 'none'),

                Repeater::make('tiered_pricing')
                    ->schema([
                        TextInput::make('min_quantity')
                            ->numeric()
                            ->required()
                            ->label('Minimum Quantity'),
                        
                        Select::make('price_type')
                            ->options([
                                'flat' => 'Flat Amount',
                                'percentage' => 'Percentage Markup'
                            ])
                            ->required()
                            ->reactive(),
                            
                        TextInput::make('price_value')
                            ->numeric()
                            ->required()
                            ->prefix(fn (callable $get) => $get('price_type') === 'percentage' ? '%' : '$')
                            ->label('Price Value')
                    ])
                    ->columns(3)
                    ->hidden(fn (callable $get) => $get('price_override_type') !== 'tiered')
                    ->label('Tiered Pricing Rules'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('exportTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        $headers = [
                            'sku',
                            'catalog_status',
                            'price_override_type',
                            'price_override'
                        ];
                        
                        $csvContent = implode(',', $headers) . "\n";
                        $csvContent .= "example-sku,default,none,\n";
                        
                        return response()->streamDownload(function () use ($csvContent) {
                            echo $csvContent;
                        }, 'connection-pair-products-template.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),
                    
                Tables\Actions\Action::make('importProducts')
                    ->label('Import from CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('csv_file')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv'])
                            ->required()
                            ->disk('local')
                    ])
                    ->action(function (array $data) {
                        $connectionPairId = request()->get('tableFilters')['connection_pair'];
                        $connectionPair = ConnectionPair::find($connectionPairId);
                        
                        $path = Storage::disk('local')->path($data['csv_file']);
                        $csv = Reader::createFromPath($path);
                        $csv->setHeaderOffset(0);
                        
                        $records = $csv->getRecords();
                        
                        foreach ($records as $record) {
                            $product = Product::where('sku', $record['sku'])
                                ->where('supplier_id', $connectionPair->supplier_id)
                                ->first();
                                
                            if ($product) {
                                $product->connectionPairs()->syncWithoutDetaching([
                                    $connectionPairId => [
                                        'catalog_status' => $record['catalog_status'] ?? 'default',
                                        'price_override_type' => $record['price_override_type'] ?? 'none',
                                        'price_override' => $record['price_override'] ?? null
                                    ]
                                ]);
                            }
                        }
                        
                        Storage::disk('local')->delete($data['csv_file']);
                    }),
                    
                Tables\Actions\Action::make('exportProducts')
                    ->label('Export to CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        $connectionPairId = request()->get('tableFilters')['connection_pair'];
                        $connectionPair = ConnectionPair::find($connectionPairId);
                        
                        $products = Product::where('supplier_id', $connectionPair->supplier_id)
                            ->whereHas('connectionPairs', function ($query) use ($connectionPairId) {
                                $query->where('connection_pair_id', $connectionPairId);
                            })
                            ->with(['connectionPairs' => function ($query) use ($connectionPairId) {
                                $query->where('connection_pair_id', $connectionPairId);
                            }])
                            ->get();
                        
                        $csvContent = "sku,catalog_status,price_override_type,price_override\n";
                        
                        foreach ($products as $product) {
                            $pivot = $product->connectionPairs->first()->pivot;
                            $csvContent .= implode(',', [
                                $product->sku,
                                $pivot->catalog_status ?? 'default',
                                $pivot->price_override_type ?? 'none',
                                $pivot->price_override ?? ''
                            ]) . "\n";
                        }
                        
                        return response()->streamDownload(function () use ($csvContent) {
                            echo $csvContent;
                        }, 'connection-pair-products.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    })
            ])
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('connectionPairs.pivot.price_override')
                    ->label('Override Price')
                    ->money('USD')
                    ->default('-'),

                TextColumn::make('connectionPairs.pivot.catalog_status')
                    ->label('Catalog Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state ?? 'default') {
                        'catalog' => 'success',
                        'queue' => 'warning',
                        'default' => 'secondary',
                        default => 'secondary',
                    }),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('connection_pair')
                    ->relationship('connectionPairs', 'id')
                    ->label('Connection Pair')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('catalog_status')
                    ->options([
                        'default' => 'Default',
                        'queue' => 'Queue',
                        'catalog' => 'Catalog'
                    ])
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('updateStatus')
                    ->label('Update Status')
                    ->form([
                        Select::make('catalog_status')
                            ->label('Catalog Status')
                            ->options([
                                'default' => 'Default',
                                'queue' => 'Queue',
                                'catalog' => 'Catalog'
                            ])
                            ->required()
                    ])
                    ->action(function (Collection $records, array $data) {
                        foreach ($records as $record) {
                            $record->connectionPairs()->updateExistingPivot(
                                request()->get('tableFilters')['connection_pair'],
                                ['catalog_status' => $data['catalog_status']]
                            );
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('success')
                    ->icon('heroicon-o-check'),

                Tables\Actions\BulkAction::make('updatePricing')
                    ->label('Update Pricing')
                    ->form([
                        Select::make('price_override_type')
                            ->label('Price Override Type')
                            ->options([
                                'none' => 'No Override',
                                'flat' => 'Flat Amount',
                                'percentage' => 'Percentage Markup'
                            ])
                            ->required()
                            ->reactive(),

                        TextInput::make('price_override')
                            ->label('Price Override Value')
                            ->numeric()
                            ->prefix(fn (callable $get) => $get('price_override_type') === 'percentage' ? '%' : '$')
                            ->suffix(fn (callable $get) => $get('price_override_type') === 'percentage' ? ' markup' : '')
                            ->required()
                            ->hidden(fn (callable $get) => $get('price_override_type') === 'none')
                    ])
                    ->action(function (Collection $records, array $data) {
                        foreach ($records as $record) {
                            $record->connectionPairs()->updateExistingPivot(
                                request()->get('tableFilters')['connection_pair'],
                                [
                                    'price_override_type' => $data['price_override_type'],
                                    'price_override' => $data['price_override_type'] === 'none' ? null : $data['price_override']
                                ]
                            );
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('warning')
                    ->icon('heroicon-o-currency-dollar'),

                Tables\Actions\BulkAction::make('addToQueue')
                    ->label('Add to Queue')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $record->connectionPairs()->updateExistingPivot(
                                request()->get('tableFilters')['connection_pair'],
                                ['catalog_status' => 'queue']
                            );
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('warning')
                    ->icon('heroicon-o-clock'),

                Tables\Actions\BulkAction::make('addToCatalog')
                    ->label('Add to Catalog')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $record->connectionPairs()->updateExistingPivot(
                                request()->get('tableFilters')['connection_pair'],
                                ['catalog_status' => 'catalog']
                            );
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),


            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConnectionPairProducts::route('/'),
            'create' => Pages\CreateConnectionPairProduct::route('/create'),
            'edit' => Pages\EditConnectionPairProduct::route('/{record}/edit'),
        ];
    }
}
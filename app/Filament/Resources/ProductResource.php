<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Destination;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Collection;
use Filament\Forms\Components\CheckboxList;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'fluentui-stack-20';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Product Management')
                ->tabs([
                    Tabs\Tab::make('Basic Information')
                        ->schema([
                            Card::make()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('sku')
                                                ->required()
                                                ->maxLength(255)
                                                ->unique(ignoreRecord: true),
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255),
                                            Select::make('supplier_id')
                                                ->relationship('supplier', 'name')
                                                ->required(),
                                            TextInput::make('cost_price')
                                                ->required()
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('$'),
                                            TextInput::make('retail_price')
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('$'),
                                            TextInput::make('stock_quantity')
                                                ->required()
                                                ->numeric()
                                                ->default(0),
                                            Textarea::make('description')
                                                ->columnSpan(2),
                                            Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true),
                                        ]),
                                ])
                        ]),
                    Tabs\Tab::make('Destination Pricing')
                        ->schema([
                            Card::make()
                                ->schema([
                                    Repeater::make('destinations')
                                        ->relationship()
                                        ->schema([
                                            Select::make('destination_id')
                                                ->label('Destination')
                                                ->options(Destination::pluck('name', 'id'))
                                                ->required(),
                                            TextInput::make('markup_percentage')
                                                ->label('Markup %')
                                                ->numeric()
                                                ->step(0.01)
                                                ->suffix('%'),
                                            TextInput::make('override_price')
                                                ->label('Override Price')
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('$'),
                                            Toggle::make('use_override_price')
                                                ->label('Use Override Price'),
                                            Select::make('catalog_status')
                                                ->options([
                                                    'default' => 'Default',
                                                    'queued' => 'Queued',
                                                    'in_catalog' => 'In Catalog',
                                                ])
                                                ->default('default')
                                                ->required(),
                                        ])
                                        ->columns(5)
                                ])
                        ])
                ])
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->actions([
                Tables\Actions\Action::make('override_price')
                    ->icon('heroicon-o-currency-dollar')
                    ->form([
                        TextInput::make('override_price')
                            ->label('Override Price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),
                    ])
                    ->visible(fn ($record) => $record->destinations()
                        ->whereIn('catalog_status', ['queued', 'in_catalog'])
                        ->exists())
                    ->action(function ($record, array $data) {
                        $record->destinations()
                            ->whereIn('catalog_status', ['queued', 'in_catalog'])
                            ->update([
                                'override_price' => $data['override_price'],
                                'use_override_price' => true
                            ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('addToCatalog')
                    ->label('Add to Catalog')
                    ->icon('heroicon-o-plus-circle')
                    ->requiresConfirmation()
                    ->form([
                        CheckboxList::make('destinations')
                            ->label('Select Destinations')
                            ->options(Destination::pluck('name', 'id'))
                            ->required()
                    ])
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $product) {
                            $product->destinations()
                                ->whereIn('destination_id', $data['destinations'])
                                ->update(['catalog_status' => 'in_catalog']);
                        }
                    }),
                Tables\Actions\BulkAction::make('moveToQueue')
                    ->label('Move to Queue')
                    ->icon('heroicon-o-clock')
                    ->requiresConfirmation()
                    ->form([
                        CheckboxList::make('destinations')
                            ->label('Select Destinations')
                            ->options(Destination::pluck('name', 'id'))
                            ->required()
                    ])
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $product) {
                            $product->destinations()
                                ->whereIn('destination_id', $data['destinations'])
                                ->update(['catalog_status' => 'queued']);
                        }
                    }),
                Tables\Actions\BulkAction::make('removeFromCatalog')
                    ->label('Remove from Catalog')
                    ->icon('heroicon-o-minus-circle')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->form([
                        CheckboxList::make('destinations')
                            ->label('Select Destinations')
                            ->options(Destination::pluck('name', 'id'))
                            ->required()
                    ])
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $product) {
                            $product->destinations()
                                ->whereIn('destination_id', $data['destinations'])
                                ->update(['catalog_status' => 'default']);
                        }
                    }),
            ])
            ->columns([
                TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cost_price')
                    ->money('USD')
                    ->sortable()
                    ->label('Cost Price'),
                TextColumn::make('retail_price')
                    ->money('USD')
                    ->sortable()
                    ->label('List Price')
                    ->getStateUsing(function ($record) {
                        $destination = $record->destinations()->first();
                        if ($destination && $destination->pivot->use_override_price) {
                            return $destination->pivot->override_price;
                        }
                        return $record->retail_price ?: $record->cost_price;
                    })
                    ->description(fn ($record) => 
                        $record->destinations()->where('use_override_price', true)->exists()
                            ? 'Customized'
                            : ''),
                TextColumn::make('destinations')
                    ->getStateUsing(function ($record) {
                        $overrides = $record->destinations()
                            ->where('use_override_price', true)
                            ->whereNotNull('override_price')
                            ->get()
                            ->map(function ($dest) {
                                return $dest->name . ': $' . number_format($dest->pivot->override_price, 2);
                            })
                            ->join(', ');
                        return $overrides ?: '-';
                    })
                    ->label('Price Overrides')
                    ->searchable(false)
                    ->wrap(),
                TextColumn::make('stock_quantity')
                    ->sortable(),
                SelectColumn::make('destinations.catalog_status')
                    ->options([
                        'default' => 'Default',
                        'queued' => 'Queued',
                        'in_catalog' => 'In Catalog',
                    ])
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
                SelectFilter::make('catalog_status')
                    ->options([
                        'default' => 'Default',
                        'queued' => 'Queued',
                        'in_catalog' => 'In Catalog',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            $query->whereHas('destinations', function ($q) use ($data) {
                                $q->where('catalog_status', $data['value']);
                            });
                        }
                    }),
                Filter::make('has_override')
                    ->label('Has Price Override')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereHas('destinations', function ($q) {
                            $q->where('use_override_price', true);
                        })),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('supplier')
                ->relationship('supplier', 'name'),
            SelectFilter::make('destination')
                ->relationship('destination', 'name'),
            SelectFilter::make('is_active')
                ->options([
                    '1' => 'Active',
                    '0' => 'Inactive',
                ])
                ->label('Status'),
        ];
    }
}
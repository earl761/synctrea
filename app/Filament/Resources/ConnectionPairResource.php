<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConnectionPairResource\Pages;
use App\Models\Destination;
use App\Models\Product;
use App\Models\Supplier;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Filament\Forms\Components\CheckboxList;

class ConnectionPairResource extends Resource
{
    protected static ?string $model = \App\Models\ConnectionPair::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Integrations';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Connection Pairs';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Card::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('supplier_id')
                                ->relationship('supplier', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            Select::make('destination_id')
                                ->relationship('destination', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            TextInput::make('sku_prefix')
                                ->label('SKU Prefix')
                                ->placeholder('Enter SKU prefix')
                                ->maxLength(255),
                        ]),

                    Grid::make(1)
                        ->schema([
                            \Filament\Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),

                            \Filament\Forms\Components\KeyValue::make('settings')
                                ->label('Connection Settings')
                                ->keyLabel('Setting Name')
                                ->valueLabel('Setting Value')
                                ->reorderable()
                                ->columnSpanFull(),
                        ])
                ])
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.type')
                    ->label('Supplier Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('destination.name')
                    ->label('Destination')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('destination.type')
                    ->label('Destination Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('sku_prefix')
                    ->label('SKU Prefix')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),

                SelectFilter::make('destination')
                    ->relationship('destination', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Statuses')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('manageCatalog')
                    ->label('Manage Catalog')
                    ->icon('heroicon-o-shopping-cart')
                    ->url(fn (\App\Models\ConnectionPair $record): string => route('filament.admin.resources.connection-pair-products.index', ['tableFilters[connection_pair]' => $record->id]))
                    ->openUrlInNewTab()
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListConnectionPairs::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }
}
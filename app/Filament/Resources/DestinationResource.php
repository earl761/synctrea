<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationResource\Pages;
use App\Models\Destination;
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
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\KeyValue;


class DestinationResource extends Resource

{
    protected static ?string $model = Destination::class;

    protected static ?string $navigationIcon = 'fluentui-stack-20';
    protected static ?string $navigationGroup = 'Inventory Settings';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Card::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('region')
                                ->required()
                                ->options([
                                    'US' => 'United States',
                                    'CA' => 'Canada',
                                    'UK' => 'United Kingdom',
                                    'DE' => 'Germany',
                                    'FR' => 'France',
                                    'IT' => 'Italy',
                                    'ES' => 'Spain',
                                ])
                                ->reactive(),
                            Select::make('type')
                                ->required()
                                ->options([
                                    'amazon' => 'Amazon SP-API',
                                    'prestashop' => 'PrestaShop',
                                ])
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set) => $set('credentials', [])),

                            TextInput::make('api_key')
                                ->required()
                                ->label(fn (callable $get) => $get('type') === 'amazon' ? 'AWS Access Key ID' : 'API Key'),

                            TextInput::make('api_secret')
                                ->required()
                                ->label(fn (callable $get) => $get('type') === 'amazon' ? 'AWS Secret Access Key' : 'API Secret'),

                            KeyValue::make('credentials')
                                ->required()
                                ->label('Additional Credentials')
                                ->addActionLabel('Add Credential')
                                ->keyLabel('Key')
                                ->valueLabel('Value')
                                ->default([])
                                ->reorderable(),

                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ]),
                ])
        ])->extraAttributes([
            'class' => 'space-y-6',
        ]);
    }

    public static function getHeaderActions(): array
    {
        return [
            Action::make('connect_amazon')
                ->label('Connect to Amazon')
                ->icon('heroicon-o-link')
                ->url(fn ($record) => route('amazon.auth', $record))
                ->openUrlInNewTab()
                ->visible(fn ($record) => $record->type === 'amazon')
        ];
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('region')
                ->searchable()
                ->sortable(),
            ToggleColumn::make('is_active')
                ->label('Active')
                ->sortable(),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable(),
            TextColumn::make('updated_at')
                ->dateTime()
                ->sortable(),
        ])
            ->actions([
                Action::make('connect_amazon')
                    ->label('Connect to Amazon')
                    ->icon('heroicon-o-link')
                    ->url(fn ($record) => route('amazon.auth', $record))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->type === 'amazon')
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
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'edit' => Pages\EditDestination::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }
}
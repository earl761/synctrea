<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Select;


class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'fluentui-stack-20';
    protected static ?string $navigationGroup = 'Inventory Settings';
    protected static ?int $navigationSort = 1;

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
                            Select::make('type')
                                ->options([
                                    'ingram_micro' => 'Ingram Micro',
                                    'd_and_h' => 'D & H',
                                    'woocommerce' => 'WooCommerce',
                                    'shopify' => 'Shopify',
                                    'bigcommerce' => 'BigCommerce',
                                    'square' => 'Square',
                                    'shopify_plus' => 'Shopify Plus',
                                    'magento' => 'Magento',
                                    'prestashop' => 'PrestaShop',
                                   
                                ])
                                ->required()
                                ->label('Type'),
                            Select::make('country_code')
                                ->options([
                                    'US' => 'US',
                                    'EU' => 'EU',
                                    'CA' => 'CA',
                                    'AU' => 'AU',
                                    'JP' => 'JP',
                                    'DE' => 'DE',
                                    'FR' => 'FR',
                                    'GB' => 'GB',
                                    'IT' => 'IT',
                                    'ES' => 'ES',
                                    'BR' => 'BR',
                                    'IN' => 'IN',
                                    'MX' => 'MX',
                                    'RU' => 'RU',
                                    'ZA' => 'ZA',
                                    'NL' => 'NL',
                                    'BE' => 'BE',
                                ])
                                ->required()
                                ->label('Region'),
                            TextInput::make('api_endpoint')
                                ->required()
                                ->url(),
                            TextInput::make('api_key')
                                ->required()
                                ->password()
                                ->dehydrateStateUsing(fn ($state) => encrypt($state))
                                ->dehydrated(fn ($state) => filled($state))
                                ->label('API Key'),
                            TextInput::make('api_secret')
                                ->required()
                                ->password()
                                ->dehydrateStateUsing(fn ($state) => encrypt($state))
                                ->dehydrated(fn ($state) => filled($state))
                                ->label('API Secret'),
                            TextInput::make('customer_number')
                                ->required()
                                ->label('Customer Number'),
                            TextInput::make('sender_id')
                                ->label('Sender ID'),
                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ]),
                ]),
            Card::make()
                ->schema([
                    KeyValue::make('credentials')
                        ->label('SFTP Credentials')
                        ->addButtonLabel('Add Credential')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->default([
                            'sftp_host' => '',
                            'sftp_username' => '',
                            'sftp_password' => '',
                            'sftp_path' => '/PRICE.ZIP'
                        ])
                        ->rules(['array'])
                        ->helperText('Required keys: sftp_host, sftp_username, sftp_password, sftp_path')
                        ->visible(fn ($get) => $get('type') === 'ingram_micro')
                        ->columnSpan('full')
                ])
                ->visible(fn ($get) => $get('type') === 'ingram_micro')
                ->columnSpan('full')
                ->heading('SFTP Settings')
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('api_endpoint')
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }
}
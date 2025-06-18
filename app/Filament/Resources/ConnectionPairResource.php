<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConnectionPairResource\Pages;
use App\Models\ConnectionPair;
use App\Models\Destination;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
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
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Models\PricingRule;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

class ConnectionPairResource extends Resource
{
    protected static ?string $model = ConnectionPair::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Integrations';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Connection Pairs';

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user && $user->can('view_connection_pairs');
    }

    public static function form(Form $form): Form
    {
        /** @var User $user */
        $user = Auth::user();

        return $form->schema([
            Card::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->required()
                                ->visible(fn () => $user->isSuperAdmin())
                                ->searchable()
                                ->preload(),

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
                        ])
                ])
                ->columnSpan('full'),

            Card::make()
                ->schema([
                    \Filament\Forms\Components\Section::make('Pricing Rules')
                        ->description('Configure pricing rules for this connection pair. These rules will be applied to all products in this connection.')
                        ->schema([
                            \Filament\Forms\Components\Toggle::make('create_pricing_rule')
                                ->label('Create Pricing Rule')
                                ->helperText('Enable this to create a pricing rule for this connection pair')
                                ->reactive()
                                ->default(false),

                            Grid::make(2)
                                ->schema([
                                    TextInput::make('pricing_rule_name')
                                        ->label('Rule Name')
                                        ->placeholder('e.g., Standard Markup for Amazon')
                                        ->maxLength(255)
                                        ->visible(fn (callable $get) => $get('create_pricing_rule'))
                                        ->required(fn (callable $get) => $get('create_pricing_rule')),

                                    Select::make('pricing_rule_type')
                                        ->label('Rule Type')
                                        ->options([
                                            PricingRule::RULE_TYPE_PERCENTAGE_MARKUP => 'Percentage Markup',
                                            PricingRule::RULE_TYPE_FLAT_MARKUP => 'Flat Markup',
                                            PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT => 'Percentage + Amount',
                                            PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE => 'Amount + Percentage',
                                        ])
                                        ->visible(fn (callable $get) => $get('create_pricing_rule'))
                                        ->required(fn (callable $get) => $get('create_pricing_rule'))
                                        ->reactive(),

                                    TextInput::make('pricing_rule_value')
                                        ->label(fn (callable $get) => $get('pricing_rule_type') === PricingRule::RULE_TYPE_PERCENTAGE_MARKUP ? 'Percentage (%)' : 'Amount ($)')
                                        ->numeric()
                                        ->visible(fn (callable $get) => $get('create_pricing_rule') && in_array($get('pricing_rule_type'), [
                                            PricingRule::RULE_TYPE_PERCENTAGE_MARKUP,
                                            PricingRule::RULE_TYPE_FLAT_MARKUP
                                        ]))
                                        ->required(fn (callable $get) => $get('create_pricing_rule') && in_array($get('pricing_rule_type'), [
                                            PricingRule::RULE_TYPE_PERCENTAGE_MARKUP,
                                            PricingRule::RULE_TYPE_FLAT_MARKUP
                                        ])),

                                    TextInput::make('pricing_rule_percentage')
                                        ->label('Percentage (%)')
                                        ->numeric()
                                        ->visible(fn (callable $get) => $get('create_pricing_rule') && in_array($get('pricing_rule_type'), [
                                            PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT,
                                            PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE
                                        ]))
                                        ->required(fn (callable $get) => $get('create_pricing_rule') && in_array($get('pricing_rule_type'), [
                                            PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT,
                                            PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE
                                        ])),

                                    TextInput::make('pricing_rule_amount')
                                        ->label('Amount ($)')
                                        ->numeric()
                                        ->visible(fn (callable $get) => $get('create_pricing_rule') && in_array($get('pricing_rule_type'), [
                                            PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT,
                                            PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE
                                        ]))
                                        ->required(fn (callable $get) => $get('create_pricing_rule') && in_array($get('pricing_rule_type'), [
                                            PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT,
                                            PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE
                                        ])),

                                    TextInput::make('pricing_rule_priority')
                                        ->label('Priority')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('Lower numbers have higher priority')
                                        ->visible(fn (callable $get) => $get('create_pricing_rule'))
                                        ->required(fn (callable $get) => $get('create_pricing_rule')),
                                ])
                                ->visible(fn (callable $get) => $get('create_pricing_rule')),
                        ])
                ])
                ->columnSpan('full')
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: !$user->isSuperAdmin()),

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
                SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->visible(fn () => $user->isSuperAdmin()),

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
                    ->url(function (\App\Models\ConnectionPair $record): string {
                        return route('filament.admin.resources.connection-pair-products.index', ['connection_pair_id' => $record->id]);
                    })
                    ->color('success'),
                Tables\Actions\Action::make('syncProducts')
                    ->label('Sync Products')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (\App\Models\ConnectionPair $record): void {
                        if ($record->destination->type === 'amazon') {
                            $command = 'amazon:sync-catalog --connection-pair-id=' . $record->id;
                        } elseif ($record->destination->type === 'prestashop') {
                            $command = 'sync:prestashop-products --connection-pair-id=' . $record->id;
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Sync not supported')
                                ->body('Sync is not supported for this destination type.')
                                ->send();
                            return;
                        }
                        
                        try {
                            Artisan::call($command);
                            Notification::make()
                                ->success()
                                ->title('Sync started')
                                ->body('Product synchronization has been initiated.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Sync failed')
                                ->body('Failed to start product synchronization: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('moveToDefault')
                        ->label('Move Products to Default')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Move Products to Default Status')
                        ->modalDescription('This will move all products in the selected connection pairs to default catalog status.')
                        ->modalSubmitActionLabel('Move to Default')
                        ->action(function (Collection $records): void {
                            $totalUpdated = 0;
                            
                            foreach ($records as $connectionPair) {
                                $updated = \App\Models\ConnectionPairProduct::where('connection_pair_id', $connectionPair->id)
                                    ->where('catalog_status', '!=', \App\Models\ConnectionPairProduct::STATUS_DEFAULT)
                                    ->update(['catalog_status' => \App\Models\ConnectionPairProduct::STATUS_DEFAULT]);
                                
                                $totalUpdated += $updated;
                            }
                            
                            if ($totalUpdated > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Products moved to default')
                                    ->body("{$totalUpdated} products have been moved to default status.")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('No products updated')
                                    ->body('All products in the selected connection pairs are already in default status.')
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
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

    // Tenant scoping is now handled automatically by the BelongsToTenant trait

    public static function getHeaderActions(): array
    {
        return [
            Action::make('managePricingRules')
                ->label('Manage Pricing Rules')
                ->icon('heroicon-o-currency-dollar')
                ->modalHeading('Manage Pricing Rules')
                ->modalDescription('Configure pricing rules for this connection pair.')
                ->form([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('rule_type')
                            ->options([
                                PricingRule::RULE_TYPE_PERCENTAGE_MARKUP => 'Percentage Markup',
                                PricingRule::RULE_TYPE_FLAT_MARKUP => 'Flat Markup',
                                PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT => 'Percentage + Amount',
                                PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE => 'Amount + Percentage',
                            ])
                            ->required()
                            ->reactive(),
                        TextInput::make('value')
                            ->label(fn ($get) => $get('rule_type') === PricingRule::RULE_TYPE_PERCENTAGE_MARKUP ? 'Percentage (%)' : 'Amount ($)')
                            ->numeric()
                            ->visible(fn ($get) => in_array($get('rule_type'), [
                                PricingRule::RULE_TYPE_PERCENTAGE_MARKUP,
                                PricingRule::RULE_TYPE_FLAT_MARKUP
                            ]))
                            ->required(fn ($get) => in_array($get('rule_type'), [
                                PricingRule::RULE_TYPE_PERCENTAGE_MARKUP,
                                PricingRule::RULE_TYPE_FLAT_MARKUP
                            ])),
                        TextInput::make('percentage_value')
                            ->label('Percentage (%)')
                            ->numeric()
                            ->visible(fn ($get) => in_array($get('rule_type'), [
                                PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT,
                                PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE
                            ]))
                            ->required(fn ($get) => in_array($get('rule_type'), [
                                PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT,
                                PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE
                            ])),
                        TextInput::make('amount_value')
                            ->label('Amount ($)')
                            ->numeric()
                            ->visible(fn ($get) => in_array($get('rule_type'), [
                                PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT,
                                PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE
                            ]))
                            ->required(fn ($get) => in_array($get('rule_type'), [
                                PricingRule::RULE_TYPE_PERCENTAGE_AMOUNT,
                                PricingRule::RULE_TYPE_AMOUNT_PERCENTAGE
                            ])),
                        TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                ])
                ->action(function (array $data, $record): void {
                    // Find existing rule or create new one
                    $rule = PricingRule::updateOrCreate(
                        [
                            'supplier_id' => $record->supplier_id,
                            'destination_id' => $record->destination_id,
                            'type' => PricingRule::TYPE_GLOBAL_CONNECTION,
                        ],
                        [
                            'name' => $data['name'],
                            'rule_type' => $data['rule_type'],
                            'value' => $data['value'] ?? null,
                            'percentage_value' => $data['percentage_value'] ?? null,
                            'amount_value' => $data['amount_value'] ?? null,
                            'priority' => $data['priority'],
                            'is_active' => true,
                        ]
                    );

                    Notification::make()
                        ->success()
                        ->title('Pricing rules updated')
                        ->body('The pricing rules have been updated and prices are being recalculated.')
                        ->send();
                })
                ->modalSubmitActionLabel('Save & Recalculate Prices')
                ->modalWidth('lg')
                ->after(function ($record) {
                    // Prices will be automatically recalculated via the PricingRuleObserver
                })
                ->loadable(function ($record) {
                    // Load existing rule if it exists
                    $rule = PricingRule::where([
                        'supplier_id' => $record->supplier_id,
                        'destination_id' => $record->destination_id,
                        'type' => PricingRule::TYPE_GLOBAL_CONNECTION,
                    ])->first();

                    if (!$rule) {
                        return [];
                    }

                    return [
                        'name' => $rule->name,
                        'rule_type' => $rule->rule_type,
                        'value' => $rule->value,
                        'percentage_value' => $rule->percentage_value,
                        'amount_value' => $rule->amount_value,
                        'priority' => $rule->priority,
                    ];
                })
        ];
    }
}
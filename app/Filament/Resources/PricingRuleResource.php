<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PricingRuleResource\Pages;
use App\Models\PricingRule;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Pricing';

    public static function shouldRegisterNavigation(): bool
    {
        // Allow any authenticated user to see Pricing Rules in the navigation
        return (bool) \Auth::user();
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();

        // Guard: If no user, or if non-super admin and no company, return empty schema
        if (!$user || (!$user->isSuperAdmin() && !$user->company_id)) {
            return $form->schema([]);
        }

        return $form
            ->schema([
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
                                Hidden::make('company_id')
                                    ->default($user->company_id)
                                    ->visible(fn () => !$user->isSuperAdmin()),

                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Select::make('type')
                                    ->options([
                                        'global_connection' => 'Global Connection Rule',
                                        'product_specific' => 'Product-Specific Rule',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('product_id', null)),

                                Forms\Components\Select::make('supplier_id')
                                    ->relationship('supplier', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn () => $user->company_id !== null || $user->isSuperAdmin()),

                                Forms\Components\Select::make('destination_id')
                                    ->relationship('destination', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn () => $user->company_id !== null || $user->isSuperAdmin()),

                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->visible(fn (callable $get) => $get('type') === 'product_specific' && ($user->company_id !== null || $user->isSuperAdmin()))
                                    ->required(fn (callable $get) => $get('type') === 'product_specific' && ($user->company_id !== null || $user->isSuperAdmin())),

                                Forms\Components\Select::make('rule_type')
                                    ->options([
                                        'percentage_markup' => 'Percentage Markup',
                                        'flat_markup' => 'Flat Markup',
                                        'tiered' => 'Tiered Pricing',
                                        'percentage_amount' => 'Percentage + Amount',
                                        'amount_percentage' => 'Amount + Percentage',
                                    ])
                                    ->required()
                                    ->reactive(),

                                Forms\Components\TextInput::make('value')
                                    ->numeric()
                                    ->visible(fn (callable $get) => in_array($get('rule_type'), ['percentage_markup', 'flat_markup']))
                                    ->required(fn (callable $get) => in_array($get('rule_type'), ['percentage_markup', 'flat_markup']))
                                    ->label(fn (callable $get) => $get('rule_type') === 'percentage_markup' ? 'Percentage (%)' : 'Amount'),

                                Forms\Components\TextInput::make('percentage_value')
                                    ->numeric()
                                    ->visible(fn (callable $get) => in_array($get('rule_type'), ['percentage_amount', 'amount_percentage']))
                                    ->required(fn (callable $get) => in_array($get('rule_type'), ['percentage_amount', 'amount_percentage']))
                                    ->label('Percentage (%)'),

                                Forms\Components\TextInput::make('amount_value')
                                    ->numeric()
                                    ->visible(fn (callable $get) => in_array($get('rule_type'), ['percentage_amount', 'amount_percentage']))
                                    ->required(fn (callable $get) => in_array($get('rule_type'), ['percentage_amount', 'amount_percentage']))
                                    ->label('Amount'),

                                Forms\Components\Repeater::make('tiers')
                                    ->schema([
                                        Forms\Components\TextInput::make('min_quantity')
                                            ->numeric()
                                            ->required(),
                                        Forms\Components\Select::make('type')
                                            ->options([
                                                'percentage' => 'Percentage',
                                                'fixed' => 'Fixed Amount',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('value')
                                            ->numeric()
                                            ->required(),
                                    ])
                                    ->visible(fn (callable $get) => $get('rule_type') === 'tiered')
                                    ->required(fn (callable $get) => $get('rule_type') === 'tiered')
                                    ->columns(3),

                                Forms\Components\TextInput::make('priority')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\Toggle::make('is_active')
                                    ->default(true)
                                    ->required(),

                                Forms\Components\DateTimePicker::make('valid_from'),

                                Forms\Components\DateTimePicker::make('valid_until'),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
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
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\BadgeColumn::make('type')
                    ->state([
                        'global_connection' => 'Global Connection',
                        'product_specific' => 'Product-Specific',
                    ])
                    ->colors([
                        'primary' => 'global_connection',
                        'warning' => 'product_specific',
                    ]),
                Tables\Columns\TextColumn::make('supplier.name'),
                Tables\Columns\TextColumn::make('destination.name'),
                Tables\Columns\TextColumn::make('product.name')
                    ->visible(fn ($record) => $record && $record->type === 'product_specific'),
                Tables\Columns\BadgeColumn::make('rule_type')
                    ->state([
                        'percentage_markup' => 'Percentage',
                        'flat_markup' => 'Flat',
                        'tiered' => 'Tiered',
                        'percentage_amount' => 'Percentage + Amount',
                        'amount_percentage' => 'Amount + Percentage'
                    ])
                    ->colors([
                        'success' => 'percentage_markup',
                        'info' => 'flat_markup',
                        'warning' => 'tiered',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('priority'),
                Tables\Columns\TextColumn::make('valid_from')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'global_connection' => 'Global Connection',
                        'product_specific' => 'Product-Specific',
                    ]),
                Tables\Filters\SelectFilter::make('rule_type')
                    ->options([
                        'percentage_markup' => 'Percentage Markup',
                        'flat_markup' => 'Flat Markup',
                        'tiered' => 'Tiered Pricing',
                        'percentage_amount' => 'Percentage + Amount',
                        'amount_percentage' => 'Amount + Percentage'
                    ]),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
                Tables\Filters\SelectFilter::make('destination')
                    ->relationship('destination', 'name'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPricingRules::route('/'),
            'create' => Pages\CreatePricingRule::route('/create'),
            'edit' => Pages\EditPricingRule::route('/{record}/edit'),
        ];
    }

    // Tenant scoping is now handled automatically by the BelongsToTenant trait
}
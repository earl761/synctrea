<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPackageResource\Pages;
use App\Filament\Resources\SubscriptionPackageResource\RelationManagers;
use App\Models\SubscriptionPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SubscriptionPackageResource extends Resource
{
    protected static ?string $model = SubscriptionPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user && $user->isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\Select::make('billing_cycle')
                            ->required()
                            ->options([
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ])
                            ->default('monthly'),

                        Forms\Components\TextInput::make('max_users')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1),

                        Forms\Components\TextInput::make('max_connections')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),

                        Forms\Components\KeyValue::make('features')
                            ->keyLabel('Feature')
                            ->valueLabel('Description')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge(),
                Tables\Columns\TextColumn::make('max_users')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_connections')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('companies_count')
                    ->counts('companies')
                    ->label('Companies'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->options([
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListSubscriptionPackages::route('/'),
            'create' => Pages\CreateSubscriptionPackage::route('/create'),
            'edit' => Pages\EditSubscriptionPackage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('companies');
    }
}

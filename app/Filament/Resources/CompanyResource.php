<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\Settings\StripeSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Filament\Notifications\Notification;
use App\Notifications\SubscriptionConfirmation;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        // /** @var User $user */
        // $user = Auth::user();
        // return $user && $user->can('view_company_details');
        return (bool) \Auth::user();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
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
                                Forms\Components\Select::make('subscription_package_id')
                                    ->relationship('subscriptionPackage', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
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
                                    ]),
                                Forms\Components\Select::make('subscription_status')
                                    ->required()
                                    ->options([
                                        'inactive' => 'Inactive',
                                        'active' => 'Active',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('inactive'),
                                Forms\Components\DateTimePicker::make('subscription_ends_at'),
                            ])
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subscriptionPackage.name')
                    ->label('Package')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscription_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'cancelled' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('subscription_ends_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_package')
                    ->relationship('subscriptionPackage', 'name'),
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->options([
                        'inactive' => 'Inactive',
                        'active' => 'Active',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manage_subscription')
                    ->label('Manage Subscription')
                    ->icon('heroicon-o-credit-card')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('action')
                            ->label('Subscription Action')
                            ->options([
                                'activate' => 'Activate Subscription',
                                'cancel' => 'Cancel Subscription',
                                'reactivate' => 'Reactivate Subscription',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Subscription End Date')
                            ->required()
                            ->default(now()->addMonth()),
                    ])
                    ->action(function (array $data, Company $record): void {
                        $stripeSettings = app(StripeSettings::class);
                        
                        if (!$stripeSettings->isConfigured()) {
                            Notification::make()
                                ->title('Stripe is not configured')
                                ->warning()
                                ->body('Please configure Stripe settings before managing subscriptions.')
                                ->send();
                            return;
                        }

                        match ($data['action']) {
                            'activate' => $this->activateSubscription($record, $data['ends_at']),
                            'cancel' => $this->cancelSubscription($record),
                            'reactivate' => $this->reactivateSubscription($record, $data['ends_at']),
                        };
                    })
                    ->visible(fn (Company $record) => Auth::user()->isSuperAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function activateSubscription(Company $company, string $endsAt): void
    {
        $company->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => $endsAt,
        ]);

        $company->notify(new SubscriptionConfirmation($company, 'created'));

        Notification::make()
            ->title('Subscription activated')
            ->success()
            ->send();
    }

    protected function cancelSubscription(Company $company): void
    {
        $company->update([
            'subscription_status' => 'cancelled',
        ]);

        $company->notify(new SubscriptionConfirmation($company, 'cancelled'));

        Notification::make()
            ->title('Subscription cancelled')
            ->success()
            ->send();
    }

    protected function reactivateSubscription(Company $company, string $endsAt): void
    {
        $company->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => $endsAt,
        ]);

        $company->notify(new SubscriptionConfirmation($company, 'created'));

        Notification::make()
            ->title('Subscription reactivated')
            ->success()
            ->send();
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
} 
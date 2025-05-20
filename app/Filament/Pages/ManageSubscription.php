<?php

namespace App\Filament\Pages;

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Settings\StripeSettings;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use App\Notifications\SubscriptionConfirmation;
use Illuminate\Http\RedirectResponse;

class ManageSubscription extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Account';
    protected static ?string $title = 'Manage Subscription';
    protected static ?string $navigationLabel = 'Subscription';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.manage-subscription';

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user && ($user->company_id || $user->isSuperAdmin());
    }

    public function mount(): ?RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Allow super admins to access the page even without a company
        if (!$user->company_id && !$user->isSuperAdmin()) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        if (request()->has('success') && request()->success) {
            Notification::make()
                ->title('Subscription updated successfully')
                ->success()
                ->send();
        }

        if (request()->has('canceled')) {
            Notification::make()
                ->title('Subscription update canceled')
                ->warning()
                ->send();
        }

        return null;
    }

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user();
        
        // If super admin without company, show all packages
        if ($user->isSuperAdmin() && !$user->company_id) {
            return [
                'isSuperAdmin' => true,
                'company' => null,
                'currentPackage' => null,
                'allPackages' => SubscriptionPackage::all(),
            ];
        }

        return [
            'isSuperAdmin' => $user->isSuperAdmin(),
            'company' => $user->company,
            'currentPackage' => $user->company?->subscriptionPackage,
            'allPackages' => SubscriptionPackage::all(),
        ];
    }

    protected function getHeaderActions(): array
    {
        /** @var User $user */
        $user = Auth::user();
        
        // If super admin without company, don't show any actions
        if ($user->isSuperAdmin() && !$user->company_id) {
            return [];
        }

        $company = $user->company;

        return [
            Action::make('upgrade')
                ->label('Upgrade Plan')
                ->icon('heroicon-o-arrow-up-circle')
                ->action(function (array $data) use ($company): void {
                    $stripeSettings = app(StripeSettings::class);
                    
                    if (!$stripeSettings->isConfigured()) {
                        Notification::make()
                            ->title('Payment system not configured')
                            ->warning()
                            ->body('Please contact support.')
                            ->send();
                        return;
                    }

                    $package = SubscriptionPackage::findOrFail($data['package_id']);
                    $checkoutUrl = $company->createStripeCheckoutSession($package);

                    redirect()->away($checkoutUrl);
                })
                ->form([
                    Section::make('Upgrade Subscription')
                        ->description('Choose a new subscription package')
                        ->schema([
                            \Filament\Forms\Components\Select::make('package_id')
                                ->label('New Package')
                                ->options(function () use ($company) {
                                    $currentPackage = $company->subscriptionPackage;
                                    if (!$currentPackage) {
                                        return SubscriptionPackage::pluck('name', 'id');
                                    }
                                    return SubscriptionPackage::where('price', '>', $currentPackage->price)
                                        ->pluck('name', 'id');
                                })
                                ->required(),
                        ]),
                ])
                ->visible(fn () => $company->isSubscriptionActive()),

            Action::make('subscribe')
                ->label('Subscribe Now')
                ->icon('heroicon-o-plus-circle')
                ->action(function (array $data) use ($company): void {
                    $stripeSettings = app(StripeSettings::class);
                    
                    if (!$stripeSettings->isConfigured()) {
                        Notification::make()
                            ->title('Payment system not configured')
                            ->warning()
                            ->body('Please contact support.')
                            ->send();
                        return;
                    }

                    $package = SubscriptionPackage::findOrFail($data['package_id']);
                    $checkoutUrl = $company->createStripeCheckoutSession($package);

                    redirect()->away($checkoutUrl);
                })
                ->form([
                    Section::make('New Subscription')
                        ->description('Choose a subscription package')
                        ->schema([
                            \Filament\Forms\Components\Select::make('package_id')
                                ->label('Package')
                                ->options(SubscriptionPackage::pluck('name', 'id'))
                                ->required(),
                        ]),
                ])
                ->visible(fn () => !$company->isSubscriptionActive()),

            Action::make('cancel')
                ->label('Cancel Subscription')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () use ($company): void {
                    if ($company->stripe_subscription_id) {
                        $company->stripe()->subscriptions->cancel($company->stripe_subscription_id);
                    }

                    $company->update([
                        'subscription_status' => 'cancelled',
                    ]);

                    $company->notify(new SubscriptionConfirmation($company, 'cancelled'));

                    Notification::make()
                        ->title('Subscription cancelled')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $company->isSubscriptionActive()),
        ];
    }
} 
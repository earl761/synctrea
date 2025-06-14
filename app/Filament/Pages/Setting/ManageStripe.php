<?php

namespace App\Filament\Pages\Setting;

use App\Filament\Clusters\Settings;
use App\Settings\StripeSettings;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Support\Facades\FilamentView;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

class ManageStripe extends SettingsPage
{
    use HasPageShield;

    protected static ?string $cluster = Settings::class;
    
    protected static ?string $navigationIcon = 'fluentui-payment-20';
    protected static ?string $title = 'Stripe Settings';
    protected static ?string $navigationLabel = 'Stripe';
    protected static ?int $navigationSort = 7;

    protected static string $settings = StripeSettings::class;

    public static function shouldRegisterNavigation(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user && $user->isSuperAdmin();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Stripe Configuration')
                    ->description('Configure your Stripe API credentials for payment processing.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('stripe_key')
                                ->label('Stripe Publishable Key')
                                ->required()
                                ->string()
                                ->autocomplete(false),

                            TextInput::make('stripe_secret')
                                ->label('Stripe Secret Key')
                                ->required()
                                ->string()
                                ->password()
                                ->autocomplete(false),

                            TextInput::make('stripe_webhook_secret')
                                ->label('Stripe Webhook Secret')
                                ->required()
                                ->string()
                                ->password()
                                ->autocomplete(false),

                            Toggle::make('stripe_test_mode')
                                ->label('Test Mode')
                                ->helperText('Enable test mode to use Stripe test credentials')
                                ->default(true),
                        ]),
                    ]),
            ]);
    }

    protected function afterSave(): void
    {
        $settings = app(StripeSettings::class);
        
        // Update Stripe config
        Config::set('cashier.key', $settings->stripe_key);
        Config::set('cashier.secret', $settings->stripe_secret);
        Config::set('cashier.webhook.secret', $settings->stripe_webhook_secret);

        Notification::make()
            ->title('Stripe settings updated successfully')
            ->success()
            ->send();
    }
}
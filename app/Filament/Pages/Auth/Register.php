<?php

namespace App\Filament\Pages\Auth;

use App\Models\Company;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Settings\MailSettings;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Register extends BaseRegister
{

    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        if ($packageId = request()->get('package')) {
            $this->form->fill([
                'package_id' => $packageId,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')
                    ->label('Username')
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class),
                TextInput::make('firstname')
                    ->label('First Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('lastname')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(255),
                $this->getEmailFormComponent(),
                $this->getCompanyNameFormComponent(),
                $this->getPackageFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ])
            ->statePath('data');
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/register.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(User::class);
    }

    protected function getCompanyNameFormComponent(): Component
    {
        return TextInput::make('company_name')
            ->label('Company Name')
            ->required()
            ->maxLength(255);
    }

    protected function getPackageFormComponent(): Component
    {
        return Select::make('package_id')
            ->label('Subscription Package')
            ->options(SubscriptionPackage::pluck('name', 'id'))
            ->required()
            ->searchable()
            ->default(request()->get('package'));
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/register.form.password.label'))
            ->password()
            ->required()
            ->rule(\Illuminate\Validation\Rules\Password::default())
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('password_confirmation')
            ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
            ->password()
            ->required()
            ->same('password')
            ->dehydrateStateUsing(fn ($state) => Hash::make($state));
    }

    public function register(): ?\Filament\Http\Responses\Auth\Contracts\RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        // Create company
        $company = Company::create([
            'name' => $data['company_name'],
            'slug' => Str::slug($data['company_name']),
        ]);

        // Create user
        $user = User::create([
            'username' => $data['username'],
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'password' => $data['password'],
            'company_id' => $company->id,
        ]);

        // Assign company admin role
        $user->assignRole('company_admin');

        // Set subscription package
        $company->update([
            'subscription_package_id' => $data['package_id'],
            'subscription_status' => 'pending',
        ]);

        event(new Registered($user));

        // Log in the user first
        Filament::auth()->login($user);
        session()->regenerate();

        try {
            $settings = app(MailSettings::class);
            
            if ($settings->isMailSettingsConfigured()) {
                // Use configured mail settings
                $settings->loadMailSettingsToConfig();
            } else {
                // Use log driver as fallback
                Config::set('mail.default', 'log');
                Log::info('Using log driver for verification email as mail settings are not configured');
            }
            
            // Send verification email
            $this->sendEmailVerificationNotification($user);

            Notification::make()
                ->title('Account created successfully')
                ->body($settings->isMailSettingsConfigured() 
                    ? 'A verification email has been sent to your email address.'
                    : 'Account created. Email verification is currently unavailable until mail settings are configured.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Failed to send verification email during registration', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Account created successfully')
                ->body('However, there was an issue sending the verification email. You can request a new verification email later.')
                ->warning()
                ->send();
        }

        return app(RegistrationResponse::class);
    }

    protected function afterRegistration(): void
    {
        $settings = app(MailSettings::class);

        if ($settings->isMailSettingsConfigured()) {
            $settings->loadMailSettingsToConfig();
            
            $this->user->notify(new WelcomeNotification());

            Notification::make()
                ->success()
                ->title('Welcome to ' . config('app.name'))
                ->body('Your account has been created successfully.')
                ->send();
        }
    }
}
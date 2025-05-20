<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Notifications\WelcomeNotification;
use App\Settings\MailSettings;
use Exception;
use Filament\Facades\Filament;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $settings = app(MailSettings::class);

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        if ($settings->isMailSettingsConfigured()) {
            // Send email verification notification
            $verifyNotification = new VerifyEmail();
            $verifyNotification->url = Filament::getVerifyEmailUrl($user);
            
            // Send welcome notification
            $welcomeNotification = new WelcomeNotification();

            $settings->loadMailSettingsToConfig();

            $user->notify($verifyNotification);
            $user->notify($welcomeNotification);

            Notification::make()
                ->title(__('resource.user.notifications.verify_sent.title'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('resource.user.notifications.verify_warning.title'))
                ->body(__('resource.user.notifications.verify_warning.description'))
                ->warning()
                ->send();
        }
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Settings\MailSettings;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $theme;
    protected MailSettings $mailSettings;

    public function __construct()
    {
        $this->mailSettings = app(MailSettings::class);
        $this->theme = $this->mailSettings->getEmailThemeConfig();
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        // Only send email if account notifications are enabled
        if ($this->mailSettings->isNotificationTypeEnabled('account')) {
            return ['mail'];
        }
        
        return [];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name'))
            ->markdown('emails.auth.welcome', [
                'notifiable' => $notifiable,
                'theme' => $this->theme,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Company $company,
        protected int $daysUntilRenewal
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subscriptionPackage = $this->company->subscriptionPackage;
        
        return (new MailMessage)
            ->subject('Subscription Renewal Reminder')
            ->greeting('Hello!')
            ->line("This is a reminder that your subscription for {$this->company->name} will renew in {$this->daysUntilRenewal} days.")
            ->line("Current Package: {$subscriptionPackage->name}")
            ->line("Renewal Amount: $" . number_format($subscriptionPackage->price, 2))
            ->line("Billing Cycle: " . ucfirst($subscriptionPackage->billing_cycle))
            ->action('Manage Subscription', route('filament.admin.resources.companies.edit', $this->company))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'company_id' => $this->company->id,
            'days_until_renewal' => $this->daysUntilRenewal,
            'subscription_package' => $this->company->subscriptionPackage->name,
        ];
    }
}

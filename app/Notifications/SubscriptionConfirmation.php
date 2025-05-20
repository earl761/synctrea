<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Company $company,
        protected string $action = 'created' // 'created', 'updated', 'cancelled'
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
        $message = (new MailMessage)
            ->subject('Subscription ' . ucfirst($this->action))
            ->greeting('Hello!');

        switch ($this->action) {
            case 'created':
                $message->line("Thank you for subscribing to {$subscriptionPackage->name}!")
                    ->line("Your subscription for {$this->company->name} has been successfully created.");
                break;
            case 'updated':
                $message->line("Your subscription for {$this->company->name} has been successfully updated.")
                    ->line("New Package: {$subscriptionPackage->name}");
                break;
            case 'cancelled':
                $message->line("Your subscription for {$this->company->name} has been cancelled.")
                    ->line("Access will remain active until: " . $this->company->subscription_ends_at->format('F j, Y'));
                break;
        }

        $message->line("Package Details:")
            ->line("• Name: {$subscriptionPackage->name}")
            ->line("• Price: $" . number_format($subscriptionPackage->price, 2))
            ->line("• Billing Cycle: " . ucfirst($subscriptionPackage->billing_cycle))
            ->line("• Max Users: {$subscriptionPackage->max_users}")
            ->line("• Max Connections: {$subscriptionPackage->max_connections}");

        if ($this->action !== 'cancelled') {
            $message->action('Manage Subscription', route('filament.admin.resources.companies.edit', $this->company));
        }

        return $message->line('Thank you for using our application!');
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
            'action' => $this->action,
            'subscription_package' => $this->company->subscriptionPackage->name,
        ];
    }
}

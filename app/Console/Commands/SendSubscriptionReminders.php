<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Notifications\SubscriptionRenewalReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendSubscriptionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send subscription renewal reminders to companies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reminderDays = [30, 15, 7, 3, 1]; // Days before renewal to send reminders

        foreach ($reminderDays as $days) {
            $this->sendRemindersForDay($days);
        }

        $this->info('Subscription reminders sent successfully.');
    }

    protected function sendRemindersForDay(int $days)
    {
        $date = Carbon::now()->addDays($days)->startOfDay();

        $companies = Company::query()
            ->where('subscription_status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->whereDate('subscription_ends_at', $date)
            ->get();

        foreach ($companies as $company) {
            $company->notify(new SubscriptionRenewalReminder($company, $days));
            $this->line("Sent {$days}-day reminder to {$company->name}");
        }
    }
}

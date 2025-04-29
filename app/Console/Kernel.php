<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sync suppliers every 30 minutes
        $schedule->command('sync:suppliers')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Sync destinations every hour
        $schedule->command('sync:destinations')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Sync WooCommerce products every hour
        $schedule->command('sync:woocommerce-products')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Sync PrestaShop products every hour
        $schedule->command('sync:prestashop-products')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Sync Shopify products every hour
        $schedule->command('sync:shopify-products')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Sync D&H products daily
        $schedule->command('dh:sync-products')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Sync D&H inventory every 2 hours
        $schedule->command('dh:sync-inventory')
            ->everyTwoHours()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Sync D&H pricing every 4 hours
        $schedule->command('dh:sync-pricing')
            ->everyFourHours()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

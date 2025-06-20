<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run Ingram Micro feed update 3 times a day
        $schedule->command('ingram:feed-update')
            ->name('ingram-feed-update-morning')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Ingram Micro feed update failed (morning)');
            })
            ->then(function () {
                Artisan::call('cleanup:orphaned-connection-pair-products');
            });
            
        $schedule->command('ingram:feed-update')
            ->name('ingram-feed-update-afternoon')
            ->dailyAt('14:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Ingram Micro feed update failed (afternoon)');
            })
            ->then(function () {
                Artisan::call('cleanup:orphaned-connection-pair-products');
            });
            
        $schedule->command('ingram:feed-update')
            ->name('ingram-feed-update-evening')
            ->dailyAt('22:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Ingram Micro feed update failed (evening)');
            })
            ->then(function () {
                Artisan::call('cleanup:orphaned-connection-pair-products');
            });

        // Run Amazon bulk catalog update every minute for testing
        $schedule->call(function () {
            \App\Models\ConnectionPair::with('company')
                ->each(function ($connectionPair) {
                    // Get the connection pair and check company
                    if (!$connectionPair) {
                        return;
                    }

                    // Check if company exists and has an active subscription
                    $company = $connectionPair->company;
                    if (!$company || !$company->isSubscriptionActive()) {
                        Log::info('Skipping bulk update for connection pair ' . $connectionPair->id . ' - Inactive subscription');
                        return;
                    }

                    Log::info('Running bulk update for connection pair ' . $connectionPair->id);
                    \Illuminate\Support\Facades\Artisan::call('amazon:bulk-catalog-update', [
                        'connectionPairId' => $connectionPair->id
                    ]);
                });
        })
        ->name('amazon-bulk-catalog-update')
        ->hourly()
        ->withoutOverlapping();

        // Run connection pair product fields sync hourly
        $schedule->command('connection-pair-products:sync-fields')
            ->name('connection-pair-products-sync')
            ->hourly()
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Connection pair products sync failed');
            });

        // Run PrestaShop product sync hourly
        // $schedule->command('sync:prestashop-products')
        //     ->name('prestashop-products-sync')
        //     ->hourly()
        //     ->withoutOverlapping()
        //     ->onFailure(function () {
        //         Log::error('PrestaShop products sync failed');
        //     });

        // Run Ingram Micro Catalog sync twice daily at 6 AM and 6 PM
        // $schedule->command('ingram:sync-catalog')
        //     ->name('ingram-micro-catalog-sync')
        //     ->twiceDaily(6, 18)
        //     ->withoutOverlapping()
        //     ->onSuccess(function () {
        //         // Run price availability sync immediately after catalog sync succeeds
        //         Artisan::call('ingram:sync-price-availability');
        //         Log::info('Ingram Micro price availability sync triggered after successful catalog sync');
                
        //         // Run connection pair products sync after catalog sync
        //         Artisan::call('sync:connection-pair-products', ['--supplier' => 'ingram_micro']);
        //         Log::info('Connection pair products sync triggered after successful catalog sync');
        //     })
        //     ->onFailure(function () {
        //         Log::error('Ingram Micro catalog sync failed');
        //     });

        // Run connection pair products sync hourly
        $schedule->command('sync:connection-pair-products', ['--supplier' => 'ingram_micro'])
            ->name('connection-pair-products-sync')
            ->hourly()
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Connection pair products sync failed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        Commands\RecalculateProductPricesCommand::class,
        Commands\SyncConnectionPairProductFields::class,
        Commands\ImportConnectionPairProducts::class,
        Commands\AmazonBulkCatalogUpdateCommand::class,
        Commands\SyncAmazonCatalogCommand::class,
        //Commands\SyncIngramMicroCatalogCommand::class,
       // Commands\SyncIngramMicroPriceAvailabilityCommand::class,
       // Commands\SyncPrestaShopProductsCommand::class,
       // Commands\SyncConnectionPairProductsCommand::class,
        Commands\CleanupOrphanedConnectionPairProductsCommand::class,
    ];
}

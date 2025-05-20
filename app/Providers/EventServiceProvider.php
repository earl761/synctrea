<?php

namespace App\Providers;

use App\Models\ConnectionPairProduct;
use App\Observers\ConnectionPairProductObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\PricingRule::observe(\App\Observers\PricingRuleObserver::class);
        \App\Models\ConnectionPairProduct::observe(\App\Observers\ConnectionPairProductObserver::class);

        // Register price update listener for specific model events
        Event::listen('eloquent.saved: *', \App\Listeners\PriceUpdateListener::class);
        Event::listen('eloquent.deleted: *', \App\Listeners\PriceUpdateListener::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

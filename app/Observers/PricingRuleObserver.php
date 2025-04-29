<?php

namespace App\Observers;

use App\Models\PricingRule;
use Illuminate\Support\Facades\Event;

class PricingRuleObserver
{
    public function saved(PricingRule $pricingRule): void
    {
        // Trigger price update when any pricing rule changes
        if ($pricingRule->wasChanged()) {
            $event = new \stdClass();
            $event->model = $pricingRule;
            Event::dispatch($event);
        }
    }

    public function deleted(PricingRule $pricingRule): void
    {
        // Trigger price update when a pricing rule is deleted
        $event = new \stdClass();
        $event->model = $pricingRule;
        Event::dispatch($event);
    }
}
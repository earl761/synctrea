<?php

namespace App\Observers;

use App\Models\PricingRule;
use App\Models\ConnectionPair;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class PricingRuleObserver
{
    public function saved(PricingRule $pricingRule): void
    {
        // Trigger price update when any pricing rule changes
        if ($pricingRule->wasChanged()) {
            // Log::info('Pricing rule changed, recalculating prices', [
            //     'rule_id' => $pricingRule->id,
            //     'supplier_id' => $pricingRule->supplier_id,
            //     'destination_id' => $pricingRule->destination_id
            // ]);

            // Get all connection pairs affected by this rule
            $connectionPairIds = \App\Models\ConnectionPair::query()
                ->where('supplier_id', $pricingRule->supplier_id)
                ->where('destination_id', $pricingRule->destination_id)
                ->pluck('id');

            // Recalculate prices for each affected connection pair
            foreach ($connectionPairIds as $connectionPairId) {
                try {
                    Artisan::call('products:recalculate-prices', [
                        'connection_pair_id' => $connectionPairId
                    ]);
                    
                    Log::info('Completed price recalculation for connection pair', [
                        'connection_pair_id' => $connectionPairId
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to recalculate prices for connection pair', [
                        'connection_pair_id' => $connectionPairId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Dispatch event for other listeners
            $event = new \stdClass();
            $event->model = $pricingRule;
            Event::dispatch($event);
        }
    }

    public function deleted(PricingRule $pricingRule): void
    {
        Log::info('Pricing rule deleted, recalculating prices', [
            'rule_id' => $pricingRule->id,
            'supplier_id' => $pricingRule->supplier_id,
            'destination_id' => $pricingRule->destination_id
        ]);

        // Get all connection pairs affected by this rule
        $connectionPairIds = \App\Models\ConnectionPair::query()
            ->where('supplier_id', $pricingRule->supplier_id)
            ->where('destination_id', $pricingRule->destination_id)
            ->pluck('id');

        // Recalculate prices for each affected connection pair
        foreach ($connectionPairIds as $connectionPairId) {
            try {
                Artisan::call('products:recalculate-prices', [
                    'connection_pair_id' => $connectionPairId
                ]);
                
                // Log::info('Completed price recalculation for connection pair', [
                //     'connection_pair_id' => $connectionPairId
                // ]);
            } catch (\Exception $e) {
                Log::error('Failed to recalculate prices for connection pair', [
                    'connection_pair_id' => $connectionPairId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Dispatch event for other listeners
        $event = new \stdClass();
        $event->model = $pricingRule;
        Event::dispatch($event);
    }
}
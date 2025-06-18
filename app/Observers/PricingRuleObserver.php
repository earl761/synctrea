<?php

namespace App\Observers;

use App\Models\PricingRule;
use App\Models\ConnectionPair;
use App\Models\ConnectionPairProduct;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class PricingRuleObserver
{
    public function saved(PricingRule $pricingRule)
    {
        Log::info('PricingRule saved, triggering targeted price recalculation', [
            'pricing_rule_id' => $pricingRule->id,
            'supplier_id' => $pricingRule->supplier_id,
            'destination_id' => $pricingRule->destination_id,
            'product_id' => $pricingRule->product_id
        ]);

        try {
            // Recalculate prices for affected products only
            $this->recalculateAffectedProducts($pricingRule);
        } catch (\Exception $e) {
            Log::error('Failed to recalculate prices after PricingRule save', [
                'pricing_rule_id' => $pricingRule->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleted(PricingRule $pricingRule)
    {
        Log::info('PricingRule deleted, triggering targeted price recalculation', [
            'pricing_rule_id' => $pricingRule->id,
            'supplier_id' => $pricingRule->supplier_id,
            'destination_id' => $pricingRule->destination_id,
            'product_id' => $pricingRule->product_id
        ]);

        try {
            // Recalculate prices for affected products only
            $this->recalculateAffectedProducts($pricingRule);
        } catch (\Exception $e) {
            Log::error('Failed to recalculate prices after PricingRule deletion', [
                'pricing_rule_id' => $pricingRule->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Recalculate prices for products affected by this pricing rule
     */
    protected function recalculateAffectedProducts(PricingRule $pricingRule)
    {
        // Build query for affected ConnectionPairProducts
        $query = ConnectionPairProduct::query()
            ->join('connection_pairs', 'connection_pair_products.connection_pair_id', '=', 'connection_pairs.id')
            ->where('connection_pairs.supplier_id', $pricingRule->supplier_id)
            ->where('connection_pairs.destination_id', $pricingRule->destination_id)
            ->where('connection_pairs.is_active', true);
        
        // If this is a product-specific rule, only affect that product
        if ($pricingRule->product_id) {
            $query->where('connection_pair_products.product_id', $pricingRule->product_id);
        }
        
        $affectedProducts = $query->select('connection_pair_products.*')->get();
        
        Log::info('Recalculating prices for affected products', [
            'pricing_rule_id' => $pricingRule->id,
            'affected_count' => $affectedProducts->count()
        ]);
        
        $updatedCount = 0;
        
        foreach ($affectedProducts as $connectionPairProduct) {
            try {
                $oldPrice = $connectionPairProduct->final_price;
                $newPrice = $connectionPairProduct->calculateFinalPrice();
                
                if ($oldPrice != $newPrice) {
                    $connectionPairProduct->update(['final_price' => $newPrice]);
                    $updatedCount++;
                    
                    Log::debug('Updated product price', [
                        'connection_pair_product_id' => $connectionPairProduct->id,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to recalculate price for product', [
                    'connection_pair_product_id' => $connectionPairProduct->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Completed targeted price recalculation', [
            'pricing_rule_id' => $pricingRule->id,
            'total_affected' => $affectedProducts->count(),
            'updated_count' => $updatedCount
        ]);
    }
}
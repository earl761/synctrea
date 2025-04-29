<?php

namespace App\Listeners;

use App\Models\Product;
use App\Models\PricingRule;
use Illuminate\Support\Facades\Log;

class PriceUpdateListener
{
    public function handle($event): void
    {
        try {
            $product = null;
            $pricingRule = null;
            
            // Check if event has model property
            if (!property_exists($event, 'model')) {
                Log::warning('Price update event received without model property');
                return;
            }
            
            $model = $event->model;
            
            // Ensure model is not null
            if (!$model) {
                Log::warning('Price update event received with null model');
                return;
            }

            // Determine which model triggered the event
            if ($model instanceof Product) {
                $product = $model;
            } elseif ($model instanceof PricingRule) {
                $pricingRule = $model;
                // If a pricing rule changed, we need to update all affected products
                $this->updateAffectedProducts($pricingRule);
                return;
            }

            if ($product) {
                $this->updateProductPrice($product);
            }
        } catch (\Exception $e) {
            Log::error('Price update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'event' => $event
            ]);
        }
    }

    protected function updateProductPrice(Product $product): void
    {
        // Get applicable pricing rules for this product
        $rules = PricingRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($product) {
                $query->where('product_id', $product->id)
                    ->orWhere(function ($q) use ($product) {
                        $q->where('type', PricingRule::TYPE_GLOBAL_CONNECTION)
                          ->where('supplier_id', $product->supplier_id);
                    });
            })
            ->orderBy('priority', 'desc')
            ->get();

        $basePrice = $product->cost_price;
        $finalPrice = $basePrice;

        foreach ($rules as $rule) {
            switch ($rule->rule_type) {
                case PricingRule::RULE_TYPE_PERCENTAGE_MARKUP:
                    $finalPrice += ($basePrice * ($rule->value / 100));
                    break;

                case PricingRule::RULE_TYPE_FLAT_MARKUP:
                    $finalPrice += $rule->value;
                    break;

                case PricingRule::RULE_TYPE_TIERED:
                    $finalPrice = $this->calculateTieredPrice($basePrice, $rule->tiers);
                    break;
            }
        }

        // Update the product's retail price if it has changed
        if ($product->retail_price !== $finalPrice) {
            $product->retail_price = $finalPrice;
            $product->save();

            Log::info('Product price updated', [
                'product_id' => $product->id,
                'old_price' => $product->retail_price,
                'new_price' => $finalPrice,
                'rules_applied' => $rules->pluck('id')->toArray()
            ]);
        }
    }

    protected function updateAffectedProducts(PricingRule $rule): void
    {
        $query = Product::query();

        if ($rule->type === PricingRule::TYPE_PRODUCT_SPECIFIC) {
            $query->where('id', $rule->product_id);
        } else {
            $query->where('supplier_id', $rule->supplier_id);
        }

        $products = $query->get();

        foreach ($products as $product) {
            $this->updateProductPrice($product);
        }
    }

    protected function calculateTieredPrice(float $basePrice, array $tiers): float
    {
        $finalPrice = $basePrice;

        foreach ($tiers as $tier) {
            if ($basePrice >= $tier['min_price'] && $basePrice <= ($tier['max_price'] ?? PHP_FLOAT_MAX)) {
                if (isset($tier['percentage'])) {
                    $finalPrice += ($basePrice * ($tier['percentage'] / 100));
                } elseif (isset($tier['fixed_amount'])) {
                    $finalPrice += $tier['fixed_amount'];
                }
                break;
            }
        }

        return $finalPrice;
    }
}
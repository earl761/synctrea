<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class ConnectionPairProduct extends Model
{
    use SoftDeletes;

    protected $table = 'connection_pair_product';

    const STATUS_DEFAULT = 'default';
    const STATUS_QUEUED = 'queued';
    const STATUS_IN_CATALOG = 'in_catalog';

    const PRICE_OVERRIDE_NONE = 'none';
    const PRICE_OVERRIDE_FIXED = 'fixed';
    const PRICE_OVERRIDE_PERCENTAGE = 'percentage';

    protected $fillable = [
        'connection_pair_id',
        'product_id',
        'sku',
        'name',
        'upc',
        'condition',
        'part_number',
        'price', // Base price from supplier
        'cost_price', // Cost price from supplier
        'fila_price', // List price
        'stock',
        'weight',
        'part_number',
        'catalog_status',
        'sync_status',
        'last_synced_at',
        'price_override_type',
        'price_override',
        'final_price', // Calculated final price after all rules
        'price_rule_secondary_type',
        'price_rule_secondary_value',
        'sync_error',
        'last_sync_attempt'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'fila_price' => 'decimal:2',
        'stock' => 'integer',
        'weight' => 'decimal:2',
        'price_override' => 'decimal:2',
        'final_price' => 'decimal:2',
        'price_rule_secondary_value' => 'decimal:2'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->catalog_status) {
                $model->catalog_status = self::STATUS_DEFAULT;
            }
            if (!$model->price_override_type) {
                $model->price_override_type = self::PRICE_OVERRIDE_NONE;
            }
            $model->final_price = $model->calculateFinalPrice();
        });

        static::updating(function ($model) {
            $model->final_price = $model->calculateFinalPrice();
        });
    }

    public function connectionPair(): BelongsTo
    {
        return $this->belongsTo(ConnectionPair::class);
    }

    /**
     * Get the qualified key name for the model.
     * This ensures that when Filament adds WHERE clauses for finding records,
     * the key is properly qualified with the table name to avoid ambiguity in joins.
     */
    public function getQualifiedKeyName()
    {
        return $this->getTable() . '.' . $this->getKeyName();
    }

    /**
     * Resolve the route binding for the model.
     * This ensures that when Filament resolves records for edit pages,
     * it uses a qualified query that won't conflict with joins.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();
        
        return $this->where($this->getTable() . '.' . $field, $value)->first();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeForConnectionPair($query, $connectionPairId)
    {
        return $query->where('connection_pair_id', $connectionPairId);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('connectionPair', function ($q) {
            $q->where('is_active', true);
        });
    }

    public function scopeDefault($query)
    {
        return $query->where('catalog_status', self::STATUS_DEFAULT);
    }



    public function scopeQueued($query)
    {
        return $query->where('catalog_status', self::STATUS_QUEUED);
    }

    public function scopeInCatalog($query)
    {
        return $query->where('catalog_status', self::STATUS_IN_CATALOG);
    }

    public function calculateFinalPrice(): float
    {
        try {
            // Use the product's cost price as the base price
            $basePrice = $this->product->cost_price ?? $this->price;
            
           

            // First apply general connection pair pricing rules
            $generalRules = PricingRule::where('supplier_id', $this->connectionPair->supplier_id)
                ->where('destination_id', $this->connectionPair->destination_id)
                ->where('type', PricingRule::TYPE_GLOBAL_CONNECTION)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->get();

            Log::info('Found pricing rules', [
                'rules_count' => $generalRules->count(),
                'rules' => $generalRules->toArray()
            ]);

            foreach ($generalRules as $rule) {
                $oldPrice = $basePrice;
                $basePrice = $rule->calculatePrice($basePrice);
                
                Log::info('Applied pricing rule', [
                    'rule_id' => $rule->id,
                    'rule_type' => $rule->rule_type,
                    'old_price' => $oldPrice,
                    'new_price' => $basePrice
                ]);
            }

            // Then apply product-specific override if it exists
            if ($this->price_override_type !== self::PRICE_OVERRIDE_NONE) {
                $oldPrice = $basePrice;
                $basePrice = $this->applyPriceOverride($basePrice);
                
                Log::info('Applied price override', [
                    'override_type' => $this->price_override_type,
                    'override_value' => $this->price_override,
                    'old_price' => $oldPrice,
                    'new_price' => $basePrice
                ]);
            }

            return round($basePrice, 2);
        } catch (\Exception $e) {
            Log::error('Error calculating final price', [
                'error' => $e->getMessage(),
                'product_id' => $this->product_id,
                'connection_pair_id' => $this->connection_pair_id
            ]);
            
            return $this->price ?? 0;
        }
    }

    protected function applyPricingRule(float $price, PricingRule $rule): float
    {
        switch ($rule->rule_type) {
            case 'percentage_markup':
                return $price * (1 + ($rule->value / 100));
            
            case 'flat_markup':
                return $price + $rule->value;
            
            case 'percentage_amount':
                $afterPercentage = $price * (1 + ($rule->percentage_value / 100));
                return $afterPercentage + $rule->amount_value;
            
            case 'amount_percentage':
                $afterAmount = $price + $rule->amount_value;
                return $afterAmount * (1 + ($rule->percentage_value / 100));
            
            default:
                return $price;
        }
    }

    protected function applyPriceOverride(float $price): float
    {
        switch ($this->price_override_type) {
            case self::PRICE_OVERRIDE_FIXED:
                return (float) $this->price_override;
            
            case self::PRICE_OVERRIDE_PERCENTAGE:
                return $price * (1 + ((float) $this->price_override / 100));
            
            default:
                return $price;
        }
    }

    public function getEffectivePrice(): float
    {
        return (float) $this->final_price;
    }
}
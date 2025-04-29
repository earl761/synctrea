<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'supplier_id',
        'destination_id',
        'product_id',
        'rule_type',
        'conditions',
        'value',
        'percentage_value',
        'amount_value',
        'calculation_order',
        'tiers',
        'priority',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'conditions' => 'array',
        'tiers' => 'array',
        'value' => 'decimal:2',
        'percentage_value' => 'decimal:2',
        'amount_value' => 'decimal:2',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    // Constants
    public const TYPE_GLOBAL_CONNECTION = 'global_connection';
    public const TYPE_PRODUCT_SPECIFIC = 'product_specific';

    public const RULE_TYPE_PERCENTAGE_MARKUP = 'percentage_markup';
    public const RULE_TYPE_FLAT_MARKUP = 'flat_markup';
    public const RULE_TYPE_TIERED = 'tiered';
    public const RULE_TYPE_PERCENTAGE_AMOUNT = 'percentage_amount';
    public const RULE_TYPE_AMOUNT_PERCENTAGE = 'amount_percentage';

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            });
    }

    public function scopeGlobalRules($query)
    {
        return $query->where('type', self::TYPE_GLOBAL_CONNECTION);
    }

    public function scopeProductSpecific($query)
    {
        return $query->where('type', self::TYPE_PRODUCT_SPECIFIC);
    }

    // Helper methods
    public function calculatePrice(float $basePrice, int $quantity = 1): float
    {
        if (!$this->is_active) {
            return $basePrice;
        }

        switch ($this->rule_type) {
            case self::RULE_TYPE_PERCENTAGE_MARKUP:
                return $basePrice * (1 + ($this->value / 100));

            case self::RULE_TYPE_FLAT_MARKUP:
                return $basePrice + $this->value;

            case self::RULE_TYPE_TIERED:
                return $this->calculateTieredPrice($basePrice, $quantity);

            case self::RULE_TYPE_PERCENTAGE_AMOUNT:
                return $this->calculateCombinedPrice($basePrice, true);

            case self::RULE_TYPE_AMOUNT_PERCENTAGE:
                return $this->calculateCombinedPrice($basePrice, false);

            default:
                return $basePrice;
        }
    }

    protected function calculateTieredPrice(float $basePrice, int $quantity): float
    {
        if (!$this->tiers || empty($this->tiers)) {
            return $basePrice;
        }

        $applicableTier = collect($this->tiers)
            ->sortByDesc('min_quantity')
            ->first(function ($tier) use ($quantity) {
                return $quantity >= ($tier['min_quantity'] ?? 0);
            });

        if (!$applicableTier) {
            return $basePrice;
        }

        if ($applicableTier['type'] === 'percentage') {
            return $basePrice * (1 + ($applicableTier['value'] / 100));
        }

        return $basePrice + $applicableTier['value'];
    }

    protected function calculateCombinedPrice(float $basePrice, bool $percentageFirst): float
    {
        if ($percentageFirst) {
            $afterPercentage = $basePrice * (1 + ($this->percentage_value / 100));
            return $afterPercentage + $this->amount_value;
        } else {
            $afterAmount = $basePrice + $this->amount_value;
            return $afterAmount * (1 + ($this->percentage_value / 100));
        }
    }
}
<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PricingRule extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

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

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
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

        Log::info('Calculating price with rule', [
            'rule_id' => $this->id,
            'rule_type' => $this->rule_type,
            'base_price' => $basePrice,
            'value' => $this->value,
            'percentage_value' => $this->percentage_value,
            'amount_value' => $this->amount_value
        ]);

        $finalPrice = match ($this->rule_type) {
            self::RULE_TYPE_PERCENTAGE_MARKUP => $basePrice * (1 + ($this->value / 100)),
            self::RULE_TYPE_FLAT_MARKUP => $basePrice + $this->value,
            self::RULE_TYPE_TIERED => $this->calculateTieredPrice($basePrice, $quantity),
            self::RULE_TYPE_PERCENTAGE_AMOUNT => $this->calculateCombinedPrice($basePrice, true),
            self::RULE_TYPE_AMOUNT_PERCENTAGE => $this->calculateCombinedPrice($basePrice, false),
            default => $basePrice,
        };

        Log::info('Price calculated', [
            'rule_id' => $this->id,
            'base_price' => $basePrice,
            'final_price' => $finalPrice
        ]);

        return $finalPrice;
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
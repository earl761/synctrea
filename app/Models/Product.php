<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'supplier_id',
        'sku',
        'upc',
        'part_number',
        'type',
        'authorizedToPurchase',
        'name',
        'description',
        'brand',
        'manufacturer',
        'category',
        'cost_price',
        'retail_price',
        'stock_quantity',
        'status',
        'specifications',
        'dimensions',
        'map_price',
        'currency_code',
        'images',
        'metadata',
        'condition',
        'catalog_data',
        'quantity',
        'subcategory',
        'is_discontinued',
        'is_direct_ship',
        'has_warranty',
        'synced_at',
        'weight',
        'weight_unit',
        'height',
        'width',
        'length',
        'net_weight',
        'dimension_unit',
        'is_bulk_freight',
    ];

    protected $casts = [
        'specifications' => 'array',
        'dimensions' => 'array',
        'images' => 'array',
        'metadata' => 'array',
        'catalog_data' => 'array',
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_discontinued' => 'boolean',
        'is_direct_ship' => 'boolean', 
        'has_warranty' => 'boolean',
        'synced_at' => 'datetime'
    ];

    // Constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_OUT_OF_STOCK = 'out_of_stock';

    public const CATALOG_STATUS_DEFAULT = 'default';
    public const CATALOG_STATUS_QUEUED = 'queued';
    public const CATALOG_STATUS_IN_CATALOG = 'in_catalog';

    public const CONDITION_NEW = 'new';
    public const CONDITION_USED = 'used';
    public const CONDITION_REFURBISHED = 'refurbished';

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class, 'product_destination')
            ->withPivot([
                'destination_sku',
                'destination_id',
                'sale_price',
                'override_price',
                'markup_percentage',
                'use_override_price',
                'pricing_rules',
                'marketplace_data',
                'sync_status',
                'catalog_status',
                'last_synced_at'
            ])
            ->withTimestamps();
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function connectionPairs(): BelongsToMany
    {
        return $this->belongsToMany(ConnectionPair::class)
            ->withPivot(['catalog_status', 'price_override'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    // Helper methods
    public function pricingRules()
    {
        return $this->hasMany(PricingRule::class);
    }

    public function calculateSalePrice(Destination $destination, int $quantity = 1): float
    {
        $pivot = $this->destinations()->where('destination_id', $destination->id)->first()?->pivot;
        
        // Check for override price first
        if ($pivot && $pivot->use_override_price && $pivot->override_price) {
            return round($pivot->override_price, 2);
        }

        $basePrice = $this->cost_price;

        // Apply markup percentage if set
        if ($pivot && $pivot->markup_percentage) {
            $basePrice = $basePrice * (1 + ($pivot->markup_percentage / 100));
        }

        // Get and apply pricing rules
        $rules = PricingRule::query()
            ->where('supplier_id', $this->supplier_id)
            ->where('destination_id', $destination->id)
            ->where(function ($query) {
                $query->where('type', PricingRule::TYPE_GLOBAL_CONNECTION)
                    ->orWhere(function ($query) {
                        $query->where('type', PricingRule::TYPE_PRODUCT_SPECIFIC)
                            ->where('product_id', $this->id);
                    });
            })
            ->active()
            ->orderBy('priority', 'desc')
            ->get();


        // Apply each rule in priority order
        foreach ($rules as $rule) {
            $basePrice = $rule->calculatePrice($basePrice, $quantity);
        }

        return round($basePrice, 2);
    }

    public function updateStock(int $quantity): void
    {
        $this->update([
            'stock_quantity' => $quantity,
            'status' => $quantity > 0 ? self::STATUS_ACTIVE : self::STATUS_OUT_OF_STOCK,
        ]);
    }

    public function syncToDestination(Destination $destination): bool
    {
        try {
            $apiClient = $destination->getApiClient();
            $salePrice = $this->calculateSalePrice($destination);
            
            $result = $apiClient->updateProduct([
                'sku' => $this->sku,
                'name' => $this->name,
                'price' => $salePrice,
                'quantity' => $this->stock_quantity,
                // Add other necessary fields
            ]);

            $this->destinations()->updateExistingPivot($destination->id, [
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            // Log the error and update sync status
            $this->destinations()->updateExistingPivot($destination->id, [
                'sync_status' => 'failed',
            ]);
            
            throw $e;
        }
    }
}
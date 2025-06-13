<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConnectionPair extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'destination_id',
        'is_active',
        'sku_prefix',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['catalog_status', 'price_override'])
            ->withTimestamps();
    }

    public function syncProducts(array $products)
    {
        $syncData = collect($products)->mapWithKeys(function ($product) {
            return [
                $product['id'] => [
                    'catalog_status' => $product['catalog_status'] ?? null,
                    'price_override' => $product['price_override'] ?? null,
                ]
            ];
        })->all();

        return $this->products()->sync($syncData);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\Api\AmazonSpApiClient;
use App\Services\Api\PrestaShopApiClient;
use App\Services\Api\NeweggApiClient;

class Destination extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'region',
        'sku_prefix',
        'api_key',
        'api_secret',
        'api_endpoint',
        'seller_id',
        'credentials',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'credentials' => 'array',
        'settings' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    // Constants for destination types
    public const TYPE_AMAZON = 'amazon';
    public const TYPE_PRESTASHOP = 'prestashop';
    public const TYPE_NEWEGG = 'newegg';

    // Relationships
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'connection_pairs')
            ->withPivot('settings', 'is_active')
            ->withTimestamps()
            ->using(ConnectionPair::class);
    }

    public function connectionPairs(): HasMany
    {
        return $this->hasMany(ConnectionPair::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    // Helper methods for API integration
    public function getApiClient()
    {
        return match($this->type) {
            self::TYPE_AMAZON => new AmazonSpApiClient($this),
            self::TYPE_PRESTASHOP => new PrestaShopApiClient($this),
            self::TYPE_NEWEGG => new NeweggApiClient($this),
            default => throw new \Exception('Unsupported destination type'),
        };
    }

    public function isAmazon(): bool
    {
        return $this->type === self::TYPE_AMAZON;
    }

    public function isPrestaShop(): bool
    {
        return $this->type === self::TYPE_PRESTASHOP;
    }

    public function isNewegg(): bool
    {
        return $this->type === self::TYPE_NEWEGG;
    }

    // Region helpers for Amazon
    public function getAmazonEndpoint(): string
    {
        return match($this->region) {
            'US' => 'https://sellingpartnerapi-na.amazon.com',
            'EU' => 'https://sellingpartnerapi-eu.amazon.com',
            default => throw new \Exception('Unsupported Amazon region'),
        };
    }

    public function getFormattedSku(string $sku): string
    {
        return $this->sku_prefix ? $this->sku_prefix . $sku : $sku;
    }
}
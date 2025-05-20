<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle', // monthly, yearly
        'features',
        'is_active',
        'max_users',
        'max_connections',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'max_users' => 'integer',
        'max_connections' => 'integer',
        'sort_order' => 'integer',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}

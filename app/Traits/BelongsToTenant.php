<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Apply global scope to automatically filter by tenant
        static::addGlobalScope(new TenantScope);
        
        // Automatically set company_id when creating records
        static::creating(function ($model) {
            if (Auth::check() && !Auth::user()->isSuperAdmin() && Auth::user()->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    /**
     * Get the company that owns the model.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Scope a query to exclude tenant filtering.
     */
    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope a query to a specific tenant.
     */
    public function scopeForTenant($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
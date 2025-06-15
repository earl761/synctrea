<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Billable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Notifications\Notifiable;

class Company extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, Billable, Notifiable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'subscription_status',
        'subscription_ends_at',
        'subscription_package_id',
        'settings',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'stripe_subscription_id',
        'stripe_subscription_status',
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function connectionPairs(): HasMany
    {
        return $this->hasMany(ConnectionPair::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function subscriptionPackage(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class);
    }

    public function isSubscriptionActive(): bool
    {
        if ($this->subscription_status !== 'active') {
            return false;
        }

        if ($this->subscription_ends_at && $this->subscription_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function routeNotificationForMail()
    {
        return $this->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'company_admin');
            })
            ->pluck('email')
            ->toArray();
    }

    public function syncSubscriptionStatus()
    {
        if ($this->stripe_subscription_id) {
            $stripeSubscription = $this->stripe()->subscriptions->retrieve($this->stripe_subscription_id);
            
            $this->update([
                'stripe_subscription_status' => $stripeSubscription->status,
                'subscription_status' => $this->mapStripeStatus($stripeSubscription->status),
                'subscription_ends_at' => $stripeSubscription->current_period_end ? now()->createFromTimestamp($stripeSubscription->current_period_end) : null,
            ]);
        }
    }

    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active', 'trialing' => 'active',
            'incomplete', 'incomplete_expired', 'past_due', 'unpaid' => 'inactive',
            'canceled' => 'cancelled',
            default => 'inactive',
        };
    }

    public function createStripeCheckoutSession(SubscriptionPackage $package): string
    {
        $stripePrice = $this->getOrCreateStripePrice($package);

        $checkoutSession = $this->stripe()->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $stripePrice,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => route('filament.admin.pages.manage-subscription', ['success' => true]),
            'cancel_url' => route('filament.admin.pages.manage-subscription', ['canceled' => true]),
            'customer_email' => $this->users()->whereHas('roles', fn($q) => $q->where('name', 'company_admin'))->first()?->email,
            'metadata' => [
                'company_id' => $this->id,
                'package_id' => $package->id,
            ],
        ]);

        return $checkoutSession->url;
    }

    protected function getOrCreateStripePrice(SubscriptionPackage $package): string
    {
        // Check if price already exists
        $existingPrice = \Stripe\Price::all([
            'lookup_keys' => ["package_{$package->id}"],
            'expand' => ['data.product'],
        ])->first();

        if ($existingPrice) {
            return $existingPrice->id;
        }

        // Create product if it doesn't exist
        $product = \Stripe\Product::create([
            'name' => $package->name,
            'description' => "Subscription package for {$package->name}",
        ]);

        // Create price
        $price = \Stripe\Price::create([
            'product' => $product->id,
            'unit_amount' => $package->price * 100, // Convert to cents
            'currency' => 'usd',
            'recurring' => [
                'interval' => $package->billing_cycle === 'monthly' ? 'month' : 'year',
            ],
            'lookup_key' => "package_{$package->id}",
        ]);

        return $price->id;
    }
}
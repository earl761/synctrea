<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class StripeSettings extends Settings
{
    public ?string $stripe_key;
    public ?string $stripe_secret;
    public ?string $stripe_webhook_secret;
    public bool $stripe_test_mode;

    public static function group(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return !empty($this->stripe_key) && 
               !empty($this->stripe_secret) && 
               !empty($this->stripe_webhook_secret);
    }
} 
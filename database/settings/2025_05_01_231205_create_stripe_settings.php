<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateStripeSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('stripe.stripe_key', '');
        $this->migrator->add('stripe.stripe_secret', '');
        $this->migrator->add('stripe.stripe_webhook_secret', '');
        $this->migrator->add('stripe.stripe_test_mode', true);
    }

    public function down(): void
    {
        $this->migrator->delete('stripe.stripe_key');
        $this->migrator->delete('stripe.stripe_secret');
        $this->migrator->delete('stripe.stripe_webhook_secret');
        $this->migrator->delete('stripe.stripe_test_mode');
    }
}

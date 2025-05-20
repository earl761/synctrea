<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Str;

class SubscriptionPackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter',
                'description' => 'Perfect for small businesses just getting started',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Users' => '2 team members',
                    'Connections' => '3 connection pairs',
                    'Support' => 'Email support',
                ],
                'max_users' => 2,
                'max_connections' => 3,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'description' => 'Ideal for growing businesses',
                'price' => 99.99,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Users' => '5 team members',
                    'Connections' => '10 connection pairs',
                    'Support' => 'Priority email support',
                    'API Access' => 'Full API access',
                ],
                'max_users' => 5,
                'max_connections' => 10,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'For large organizations with complex needs',
                'price' => 299.99,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Users' => 'Unlimited team members',
                    'Connections' => 'Unlimited connection pairs',
                    'Support' => '24/7 phone and email support',
                    'API Access' => 'Full API access',
                    'Custom Integration' => 'Custom integration support',
                ],
                'max_users' => -1, // Unlimited
                'max_connections' => -1, // Unlimited
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            SubscriptionPackage::create([
                ...$package,
                'slug' => Str::slug($package['name']),
                'is_active' => true,
            ]);
        }
    }
} 
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPackage>
 */
class SubscriptionPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word . ' Package',
            'slug' => $this->faker->slug,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 10, 300),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly']),
            'features' => [
                'Users' => $this->faker->randomElement(['5 team members', '10 team members', 'Unlimited team members']),
                'Connections' => $this->faker->randomElement(['5 connection pairs', '10 connection pairs', 'Unlimited connection pairs']),
                'Support' => $this->faker->randomElement(['Email support', '24/7 phone and email support']),
            ],
            'max_users' => $this->faker->randomElement([5, 10, -1]),
            'max_connections' => $this->faker->randomElement([5, 10, -1]),
            'sort_order' => $this->faker->numberBetween(1, 5),
        ];
    }
}

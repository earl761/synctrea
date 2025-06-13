<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'slug' => $this->faker->slug,
            'description' => $this->faker->sentence,
            'subscription_status' => $this->faker->randomElement(['active', 'inactive', 'cancelled']),
            'subscription_ends_at' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            'settings' => [
                'notification_preferences' => [
                    'email' => true,
                    'sms' => false,
                ],
            ],
        ];
    }
    
    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (\App\Models\Company $company) {
            // Additional setup if needed
        });
    }
}

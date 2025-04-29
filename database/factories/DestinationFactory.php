<?php

namespace Database\Factories;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;

class DestinationFactory extends Factory
{
    protected $model = Destination::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'type' => 'amazon',
            'region' => $this->faker->randomElement(['US', 'CA', 'MX', 'UK', 'DE', 'FR', 'IT', 'ES']),
            'seller_id' => $this->faker->uuid,
            'api_key' => encrypt($this->faker->uuid),
            'api_secret' => encrypt($this->faker->uuid),
            'credentials' => [
                'refresh_token' => $this->faker->uuid
            ],
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
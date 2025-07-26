<?php

namespace Database\Factories;

use App\Models\VenuePlatform;
use Illuminate\Database\Eloquent\Factories\Factory;

class VenuePlatformFactory extends Factory
{
    protected $model = VenuePlatform::class;

    public function definition(): array
    {
        return [
            'platform_type' => fake()->randomElement(['restoo', 'covermanager']),
            'is_enabled' => true,
            'configuration' => [
                'api_key' => fake()->uuid(),
                'restaurant_id' => fake()->randomNumber(6),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function restoo(): static
    {
        return $this->state([
            'platform_type' => 'restoo',
            'configuration' => [
                'api_key' => fake()->uuid(),
                'account' => fake()->word(),
            ],
        ]);
    }

    public function covermanager(): static
    {
        return $this->state([
            'platform_type' => 'covermanager',
            'configuration' => [
                'restaurant_id' => fake()->randomNumber(6),
            ],
        ]);
    }

    public function disabled(): static
    {
        return $this->state([
            'is_enabled' => false,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\PlatformReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlatformReservationFactory extends Factory
{
    protected $model = PlatformReservation::class;

    public function definition(): array
    {
        return [
            'platform_type' => fake()->randomElement(['restoo', 'covermanager']),
            'platform_reservation_id' => fake()->uuid(),
            'platform_status' => 'confirmed',
            'synced_to_platform' => true,
            'last_synced_at' => now(),
            'platform_data' => [
                'reservation_date' => now()->addDay()->format('Y-m-d'),
                'reservation_time' => '19:30:00',
                'customer_name' => fake()->name(),
                'customer_email' => fake()->email(),
                'customer_phone' => fake()->phoneNumber(),
                'party_size' => fake()->numberBetween(2, 6),
            ],
        ];
    }

    public function restoo(): static
    {
        return $this->state([
            'platform_type' => 'restoo',
            'platform_reservation_id' => fake()->uuid(),
            'platform_data' => [
                'reservation_datetime' => now()->addDay()->toISOString(),
                'customer_name' => fake()->name(),
                'customer_email' => fake()->email(),
                'customer_phone' => fake()->phoneNumber(),
                'party_size' => fake()->numberBetween(2, 6),
                'restoo_response' => ['uuid' => fake()->uuid(), 'status' => 'confirmed'],
            ],
        ]);
    }

    public function covermanager(): static
    {
        return $this->state([
            'platform_type' => 'covermanager',
            'platform_reservation_id' => fake()->randomNumber(6),
            'platform_data' => [
                'reservation_date' => now()->addDay()->format('Y-m-d'),
                'reservation_time' => '19:30:00',
                'customer_name' => fake()->name(),
                'customer_email' => fake()->email(),
                'customer_phone' => fake()->phoneNumber(),
                'party_size' => fake()->numberBetween(2, 6),
                'covermanager_response' => ['id_reserv' => fake()->randomNumber(6), 'status' => 'confirmed'],
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'synced_to_platform' => false,
            'platform_reservation_id' => null,
        ]);
    }
}

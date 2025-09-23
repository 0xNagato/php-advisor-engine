<?php

namespace Database\Factories;

use App\Models\Concierge;
use App\Models\QrCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QrCode>
 */
class QrCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url_key' => Str::random(8),
            'name' => $this->faker->words(3, true),
            'is_active' => true,
            'qr_code_path' => 'qr-codes/'.Str::random(20).'.svg',
            'scan_count' => 0,
        ];
    }

    /**
     * Indicate that the QR code is unassigned (no concierge).
     */
    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'concierge_id' => null,
            'assigned_at' => null,
        ]);
    }

    /**
     * Indicate that the QR code is assigned.
     */
    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'concierge_id' => Concierge::factory(),
            'assigned_at' => now(),
        ]);
    }

    /**
     * Indicate that the QR code is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

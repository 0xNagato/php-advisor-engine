<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Referral>
 */
class ReferralFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referrer_id' => User::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'type' => $this->faker->randomElement(['concierge', 'venue']),
            'referrer_type' => $this->faker->randomElement(['partner', 'concierge', 'venue_manager']),
            'region_id' => 1, // Default to region 1
            'company_name' => $this->faker->company(),
        ];
    }

    /**
     * Indicate that the referral is for a concierge.
     */
    public function concierge(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'concierge',
        ]);
    }

    /**
     * Indicate that the referral is secured/accepted.
     */
    public function secured(): static
    {
        return $this->state(fn (array $attributes) => [
            'secured_at' => now(),
            'user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate that the referral is from a QR code.
     */
    public function fromQrCode(): static
    {
        return $this->state(fn (array $attributes) => [
            'qr_code_id' => \App\Models\QrCode::factory(),
        ]);
    }
}

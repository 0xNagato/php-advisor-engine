<?php

namespace Database\Factories;

use App\Models\VipCode;
use App\Models\VipSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VipSession>
 */
class VipSessionFactory extends Factory
{
    protected $model = VipSession::class;

    public function definition(): array
    {
        return [
            'vip_code_id' => VipCode::factory(),
            'token' => hash('sha256', fake()->sha1().time().fake()->numberBetween(1, 1000)),
            'sanctum_token_id' => null, // No Sanctum token for anonymous sessions
            'expires_at' => now()->addHours(24),
        ];
    }

    /**
     * Indicate that the session is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the session belongs to a specific VIP code.
     */
    public function forVipCode(VipCode $vipCode): static
    {
        return $this->state(fn (array $attributes) => [
            'vip_code_id' => $vipCode->id,
        ]);
    }
}

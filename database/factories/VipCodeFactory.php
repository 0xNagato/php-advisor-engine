<?php

namespace Database\Factories;

use App\Models\Concierge;
use App\Models\VipCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VipCode>
 */
class VipCodeFactory extends Factory
{
    protected $model = VipCode::class;

    public function definition(): array
    {
        return [
            'code' => $this->generateUniqueCode(),
            'concierge_id' => Concierge::factory(),
            'is_active' => $this->faker->boolean(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn (array $attributes) => $this->faker->dateTimeBetween($attributes['created_at'], 'now'),
        ];
    }

    protected function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (VipCode::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Indicate that the VIP code belongs to an existing concierge.
     */
    public function forExistingConcierge(): VipCodeFactory|Factory
    {
        return $this->state(function (array $attributes) {
            $concierge = Concierge::query()->inRandomOrder()->first();

            if (! $concierge) {
                // If no concierge exists, create one
                $concierge = Concierge::factory()->create();
            }

            return ['concierge_id' => $concierge->id];
        });
    }
}

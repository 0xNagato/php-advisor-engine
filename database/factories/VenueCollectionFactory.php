<?php

namespace Database\Factories;

use App\Models\Concierge;
use App\Models\VenueCollection;
use App\Models\VipCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueCollection>
 */
class VenueCollectionFactory extends Factory
{
    protected $model = VenueCollection::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => false,
            'region' => 'miami', // Default region
        ];
    }

    /**
     * Indicate that the collection belongs to a concierge.
     */
    public function forConcierge(Concierge $concierge): static
    {
        return $this->state(fn (array $attributes) => [
            'concierge_id' => $concierge->id,
            'vip_code_id' => null,
        ]);
    }

    /**
     * Indicate that the collection belongs to a VIP code.
     */
    public function forVipCode(VipCode $vipCode): static
    {
        return $this->state(fn (array $attributes) => [
            'concierge_id' => null,
            'vip_code_id' => $vipCode->id,
        ]);
    }

    /**
     * Indicate that the collection is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the collection is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

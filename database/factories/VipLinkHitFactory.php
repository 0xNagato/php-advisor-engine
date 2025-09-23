<?php

namespace Database\Factories;

use App\Models\VipCode;
use App\Models\VipLinkHit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VipLinkHit>
 */
class VipLinkHitFactory extends Factory
{
    protected $model = VipLinkHit::class;

    public function definition(): array
    {
        return [
            'vip_code_id' => VipCode::factory(),
            'code' => fake()->lexify('????????'),
            'visited_at' => now(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referer_url' => fake()->url(),
            'full_url' => fake()->url(),
            'raw_query' => 'param1=value1&param2=value2',
            'query_params' => [
                'param1' => 'value1',
                'param2' => 'value2',
            ],
        ];
    }

    /**
     * Indicate that the hit has no VIP code (invalid code).
     */
    public function withoutVipCode(): static
    {
        return $this->state(fn (array $attributes) => [
            'vip_code_id' => null,
        ]);
    }

    /**
     * Indicate that the hit has complex query params with arrays.
     */
    public function withComplexParams(): static
    {
        return $this->state(fn (array $attributes) => [
            'raw_query' => 'name=john&tags[]=food&tags[]=drinks&count=3',
            'query_params' => [
                'name' => 'john',
                'tags' => ['food', 'drinks'],
                'count' => '3',
            ],
        ]);
    }
}

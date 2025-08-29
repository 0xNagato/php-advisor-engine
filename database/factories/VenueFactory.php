<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class VenueFactory extends Factory
{
    protected $model = Venue::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'primary_contact_name' => fake()->name(),
            'contact_phone' => '+16473823326',
            'payout_venue' => 60,
            'open_days' => [
                'monday' => 'closed',
                'tuesday' => 'closed',
                'wednesday' => 'open',
                'thursday' => 'open',
                'friday' => 'open',
                'saturday' => 'open',
                'sunday' => 'open',
            ],
            'booking_fee' => 200,

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'contacts' => [
                [
                    'contact_name' => 'Alex Zhardanovsky',
                    'contact_phone' => '+19176644415',
                    'use_for_reservations' => true,
                    'preferences' => [
                        'sms' => true, 'mail' => false,
                    ],
                ],
                [
                    'contact_name' => 'Andrew Weir',
                    'contact_phone' => '+16473823326',
                    'use_for_reservations' => true,
                    'preferences' => [
                        'sms' => true, 'mail' => false,
                    ],
                ],
            ],

            'metadata' => fake()->optional(0.7)->passthrough([
                'rating' => fake()->randomFloat(1, 3.5, 5.0),
                'priceLevel' => fake()->numberBetween(1, 4),
                'reviewCount' => fake()->numberBetween(10, 500),
                'googlePlaceId' => 'ChIJ'.fake()->lexify('????????????????'),
                'lastSyncedAt' => now()->subHours(fake()->numberBetween(1, 48))->toISOString(),
            ]),

            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the venue has high ratings and is expensive
     */
    public function highEnd(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'rating' => fake()->randomFloat(1, 4.5, 5.0),
                'priceLevel' => 4,
                'reviewCount' => fake()->numberBetween(200, 1000),
                'googlePlaceId' => 'ChIJ'.fake()->lexify('????????????????'),
                'lastSyncedAt' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Indicate that the venue has no metadata yet
     */
    public function withoutMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => null,
        ]);
    }
}

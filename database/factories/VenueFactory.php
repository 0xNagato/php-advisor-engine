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
                ],
                [
                    'contact_name' => 'Andrew Weir',
                    'contact_phone' => '+16473823326',
                    'use_for_reservations' => true,
                ],
            ],

            'user_id' => User::factory(),
        ];
    }
}

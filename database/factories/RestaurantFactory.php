<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        return [
            'restaurant_name' => $this->faker->name(),
            'primary_contact_name' => $this->faker->name(),
            'contact_phone' => '+16473823326',
            'payout_restaurant' => 60,
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

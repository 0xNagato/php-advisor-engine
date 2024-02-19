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
            'contact_phone' => $this->faker->phoneNumber(),
            'secondary_contact_name' => $this->faker->name(),
            'secondary_contact_phone' => $this->faker->phoneNumber(),
            'payout_platform' => 20,
            'payout_restaurant' => 60,
            'payout_charity' => 5,
            'payout_concierge' => 15,
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

            'user_id' => User::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\RestaurantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class RestaurantProfileFactory extends Factory
{
    protected $model = RestaurantProfile::class;

    public function definition(): array
    {
        return [
            'restaurant_name' => $this->faker->name(),
            'contact_phone' => $this->faker->phoneNumber(),
            'secondary_contact_phone' => $this->faker->phoneNumber(),
            'payout_platform' => 20,
            'payout_restaurant' => 60,
            'payout_charity' => 15,
            'payout_concierge' => 5,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}

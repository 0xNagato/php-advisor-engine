<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\RestaurantProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'time_slot' => Carbon::now(),
            'guest_capacity' => $this->faker->randomNumber(),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'restaurant_profile_id' => RestaurantProfile::factory(),
        ];
    }
}

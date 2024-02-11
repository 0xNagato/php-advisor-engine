<?php

namespace Database\Factories;

use App\Models\RestaurantProfile;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'start_time' => '17:00:00',
            'end_time' => '17:30:00',
            'available_slots' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'restaurant_profile_id' => RestaurantProfile::factory(),
        ];
    }
}

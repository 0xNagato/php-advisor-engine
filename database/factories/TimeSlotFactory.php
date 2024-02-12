<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    public function definition(): array
    {
        $is_available = $this->faker->boolean;

        if ($is_available) {
            $available_slots = $this->faker->numberBetween(1, 10);
        } else {
            $available_slots = 0;
        }

        return [
            'date' => Carbon::now(),
            'start_time' => '17:00:00',
            'end_time' => '17:30:00',
            'is_available' => $is_available,
            'available_slots' => $available_slots,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'restaurant_id' => Restaurant::factory(),
        ];
    }
}

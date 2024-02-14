<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $is_available = $this->faker->boolean;

        if ($is_available) {
            $available_tables = $this->faker->numberBetween(4, 20);
        } else {
            $available_tables = 0;
        }

        return [
            'start_time' => '17:00:00',
            'end_time' => '17:30:00',
            'is_available' => $is_available,
            'available_tables' => $available_tables,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'restaurant_id' => Restaurant::factory(),
        ];
    }
}

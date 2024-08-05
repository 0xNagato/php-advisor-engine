<?php

namespace Database\Factories;

use App\Models\ScheduleTemplate;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ScheduleTemplateFactory extends Factory
{
    protected $model = ScheduleTemplate::class;

    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'day_of_week' => $this->faker->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
            'is_available' => $this->faker->boolean,
            'available_tables' => $this->faker->numberBetween(1, 20),
            'prime_time' => $this->faker->boolean,
            'prime_time_fee' => $this->faker->numberBetween(1000, 5000),
            'party_size' => $this->faker->numberBetween(2, 10),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

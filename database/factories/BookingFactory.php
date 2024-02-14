<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'guest_name' => $this->faker->name(),
            'guest_email' => $this->faker->unique()->safeEmail(),
            'guest_phone' => $this->faker->phoneNumber(),
            'guest_count' => $this->faker->numberBetween(2, 8),
            'currency' => 'USD',
            'status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'schedule_id' => Schedule::factory(),
            'concierge_id' => Concierge::factory(),
        ];
    }
}

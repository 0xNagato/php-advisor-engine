<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'guest_user_id' => $this->faker->randomNumber(),
            'guest_name' => $this->faker->name(),
            'guest_email' => $this->faker->unique()->safeEmail(),
            'guest_phone' => $this->faker->phoneNumber(),
            'guest_count' => $this->faker->randomNumber(),
            'total_fee' => $this->faker->randomNumber(),
            'currency' => 'USD',
            'status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'reservation_id' => TimeSlot::factory(),
            'concierge_user_id' => Concierge::factory(),
        ];
    }
}

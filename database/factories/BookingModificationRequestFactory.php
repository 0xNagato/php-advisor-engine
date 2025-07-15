<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingModificationRequest;
use App\Models\ScheduleTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookingModificationRequestFactory extends Factory
{
    protected $model = BookingModificationRequest::class;

    public function definition(): array
    {
        return [
            'original_guest_count' => $this->faker->randomNumber(),
            'requested_guest_count' => $this->faker->randomNumber(),
            'original_time' => Carbon::now(),
            'requested_time' => Carbon::now(),
            'original_booking_at' => Carbon::now(),
            'request_booking_at' => Carbon::now(),
            'status' => BookingModificationRequest::STATUS_PENDING,
            'meta' => $this->faker->words(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'booking_id' => Booking::factory(),
            'requested_by_id' => User::factory(),
            'original_schedule_template_id' => ScheduleTemplate::factory(),
            'requested_schedule_template_id' => ScheduleTemplate::factory(),
        ];
    }
}

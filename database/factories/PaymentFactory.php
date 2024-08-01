<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'stripe_payment_id' => fake()->word(),
            'amount' => fake()->randomNumber(),
            'status' => fake()->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'booking_id' => Booking::factory(),
        ];
    }
}

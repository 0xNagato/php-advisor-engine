<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $partners = Partner::all(); // Get all partners

        return [
            'guest_first_name' => fake()->firstName(),
            'guest_last_name' => fake()->lastName(),
            'guest_email' => fake()->unique()->safeEmail(),
            'guest_phone' => '+16473823326',
            'guest_count' => fake()->numberBetween(2, 8),
            'currency' => 'USD',
            'status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'concierge_id' => Concierge::factory(),

            'partner_concierge_id' => $partners->random()->id, // Assign a random partner
            'partner_restaurant_id' => $partners->random()->id, // Assign a random partner
        ];
    }
}

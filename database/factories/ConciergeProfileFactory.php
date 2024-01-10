<?php

namespace Database\Factories;

use App\Models\ConciergeProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ConciergeProfileFactory extends Factory
{
    protected $model = ConciergeProfile::class;

    public function definition(): array
    {
        return [
            'hotel_name' => $this->faker->name(),
            'hotel_phone' => $this->faker->phoneNumber(),
            'payout_percentage' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}

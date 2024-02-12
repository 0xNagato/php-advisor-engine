<?php

namespace Database\Factories;

use App\Models\Concierge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ConciergeFactory extends Factory
{
    protected $model = Concierge::class;

    public function definition(): array
    {
        return [
            'hotel_name' => $this->faker->name(),
            'payout_type' => 'paypal',
            'payout_account' => $this->faker->unique()->safeEmail(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}

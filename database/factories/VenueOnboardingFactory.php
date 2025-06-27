<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VenueGroup;
use App\Models\VenueOnboarding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VenueOnboardingFactory extends Factory
{
    protected $model = VenueOnboarding::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker->name(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'venue_count' => $this->faker->randomNumber(),
            'has_logos' => $this->faker->boolean(),
            'agreement_accepted' => $this->faker->boolean(),
            'agreement_accepted_at' => Carbon::now(),
            'prime_hours' => $this->faker->words(),
            'status' => $this->faker->word(),
            'processed_at' => Carbon::now(),
            'notes' => $this->faker->word(),
            'additional_notes' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'processed_by_id' => User::factory(),
            'partner_id' => User::factory(),
            'venue_group_id' => VenueGroup::factory(),
        ];
    }
}

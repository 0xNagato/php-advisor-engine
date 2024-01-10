<?php

namespace Database\Factories;

use App\Models\RestaurantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class RestaurantProfileFactory extends Factory
{
    protected $model = RestaurantProfile::class;

    public function definition(): array
    {
        return [
            'restaurant_name' => $this->faker->name(),
            'contact_phone' => $this->faker->phoneNumber(),
            'website_url' => $this->faker->url(),
            'description' => $this->faker->text(),
            'cuisines' => $this->faker->words(),
            'price_range' => $this->faker->words(),
            'sunday_hours_of_operation' => $this->faker->words(),
            'monday_hours_of_operation' => $this->faker->words(),
            'tuesday_hours_of_operation' => $this->faker->words(),
            'wednesday_hours_of_operation' => $this->faker->words(),
            'thursday_hours_of_operation' => $this->faker->words(),
            'friday_hours_of_operation' => $this->faker->words(),
            'saturday_hours_of_operation' => $this->faker->words(),
            'address_line_1' => $this->faker->address(),
            'address_line_2' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->word(),
            'zip' => $this->faker->postcode(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}

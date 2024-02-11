<?php

namespace Database\Seeders;

use App\Models\RestaurantProfile;
use App\Models\TimeSlot;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all restaurant profiles
        $restaurantProfiles = RestaurantProfile::all();

        // Determine the number of restaurant profiles to create reservations for (e.g., 80% of them)
        $percentageToSeed = 0.8;
        $countToSeed = (int) ($restaurantProfiles->count() * $percentageToSeed);

        // Get a random subset of restaurant profiles
        $restaurantProfilesToSeed = $restaurantProfiles->random($countToSeed);

        // Define the date range for the last month
        $startDate = Carbon::now()->subMonth()->startOfMonth();
        $endDate = Carbon::now()->subMonth()->endOfMonth();

        // Create reservations for the selected restaurant profiles
        foreach ($restaurantProfilesToSeed as $restaurantProfile) {
            // Generate a random date and time within the last month
            $randomDate = Carbon::createFromTimestamp($faker->dateTimeBetween($startDate, $endDate)->getTimestamp());

            // Create a reservation using the factory
            TimeSlot::create([
                'date' => $randomDate,
                'start_time' => '18:00:00',
                'end_time' => '20:00:00',
                'is_closed' => false,
                'restaurant_profile_id' => $restaurantProfile->id,
            ]);
        }
    }
}

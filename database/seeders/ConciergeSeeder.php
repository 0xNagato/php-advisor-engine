<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ConciergeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hotelNames = collect([
            'Azure Skyline Resort',
            'Golden Horizon Suites',
            'Emerald Palace Hotel',
            'Silver Pine Lodge',
            'Crystal Lake Inn',
            'Sapphire Seas Resort',
            'Ruby Rose Hotel',
            'Diamond Cliff Retreat',
            'Velvet Sunset Inn',
            'Twilight Dreams Hotel',
            'Mystic Mountain Lodge',
            'Eclipse Boutique Hotel',
            'Harbor Light Inn',
            'Mirage Oasis Hotel',
            'Celestial Haven Resort',
            'Whispering Pines Lodge',
            'Northern Lights Hotel',
            'Sunrise Sanctuary Resort',
            'Paradise Peak Suites',
            'Lunar Valley Inn',
        ]);

        $faker = Faker::create();

        $hotelNames->each(function ($hotelName) use ($faker) {
            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'),
                'phone' => $faker->phoneNumber,
            ]);

            $user->assignRole('concierge');

            $user->conciergeProfile()->create([
                'hotel_name' => $hotelName,
                'hotel_phone' => $faker->phoneNumber,
                'payout_percentage' => 15,
            ]);
        });
    }
}

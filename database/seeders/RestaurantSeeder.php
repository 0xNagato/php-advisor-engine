<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurantNames = collect([
            'The Golden Fork',
            'Mystic Pizza',
            'The Secret Ingredient',
            'Harbor Delights',
            'Saffron & Sage',
            'Twilight Tacos',
            'Basil & Barley',
            'The Urban Garden',
            'Velvet Vineyard',
            'Moonlit Meals',
            'Sizzle & Steam',
            'Aqua Essence',
            'The Spice Symphony',
            'Flavors of Fire',
            'Garnish & Glow',
            'Pinnacle Plates',
            'Whispering Bamboo',
            'Epic Eats',
            'Bounty Bistro',
            'Cherry Blossom Cafe',
        ]);

        $faker = Faker::create();

        $restaurantNames->each(function ($restaurantName) use ($faker) {
            $cuisineTypes = [
                'Italian',
                'Mexican',
                'French',
                'Japanese',
                'Chinese',
                'American',
                'Thai',
                'Indian',
                'Spanish',
                'Mediterranean',
                'Greek',
                'Lebanese',
                'Turkish',
                'Vietnamese',
                'Korean',
                'Brazilian',
                'Argentinian',
                'Ethiopian',
                'Moroccan',
                'Russian',
            ];

            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'),
                'phone' => $faker->phoneNumber,
            ]);

            $user->assignRole('restaurant');

            $user->restaurantProfile()->create([
                'restaurant_name' => $restaurantName,
                'contact_phone' => $faker->phoneNumber,
                'website_url' => $faker->url,
                'description' => Str::limit($faker->paragraph, 255),
                'cuisines' => $faker->randomElements($cuisineTypes, $faker->numberBetween(1, 5)),
                'price_range' => $faker->numberBetween(1, 5),
                'address_line_1' => $faker->streetAddress,
                'address_line_2' => $faker->secondaryAddress,
                'city' => $faker->city,
                'state' => $faker->state,
                'zip' => $faker->postcode,
                'payout_percentage' => $faker->numberBetween(50, 80),
            ]);
        });
    }
}

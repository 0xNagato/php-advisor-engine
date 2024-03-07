<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        $restaurantNames->each(function ($restaurantName) {
            $user = User::factory(['first_name' => 'Restaurant'])
                ->has(Restaurant::factory([
                    'restaurant_name' => $restaurantName,
                ]))
                ->create();

            $user->assignRole('restaurant');
        });
    }
}

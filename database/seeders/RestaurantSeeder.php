<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\Referral;
use App\Models\Restaurant;
use App\Models\User;
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
            // 'Saffron & Sage',
            // 'Twilight Tacos',
            // 'Basil & Barley',
            // 'The Urban Garden',
            // 'Velvet Vineyard',
            // 'Moonlit Meals',
            // 'Sizzle & Steam',
            // 'Aqua Essence',
            // 'The Spice Symphony',
            // 'Flavors of Fire',
            // 'Garnish & Glow',
            // 'Pinnacle Plates',
            // 'Whispering Bamboo',
            // 'Epic Eats',
            // 'Bounty Bistro',
            // 'Cherry Blossom Cafe',
        ]);

        $restaurantNames->each(function ($restaurantName) {
            $partner = Partner::inRandomOrder()->first();
            $email = 'restaurant@' . Str::slug($restaurantName) . '.com';

            $user = User::factory([
                'first_name' => 'Restaurant',
                'partner_referral_id' => $partner->id,
                'email' => $email
            ])
                ->has(Restaurant::factory([
                    'restaurant_name' => $restaurantName,
                ]))
                ->create();

            $user->assignRole('restaurant');

            Referral::create([
                'referrer_id' => $partner->user->id,
                'user_id' => $user->id,
                'email' => $email,
                'secured_at' => now(),
                'type' => 'restaurant',
                'referrer_type' => 'partner',
            ]);
        });
    }
}

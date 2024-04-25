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
            'GEKKO',
            'Casa Donna',
            'Papi Steak',
            'Swan',
            'KOMODO',
            'Call Me Gaby',
            'Sexy Fish',
            'Standard Hotel',
            'RAOs',
            'Kissaki',
        ]);

        $restaurantNames->each(function ($restaurantName) {
            $partner = Partner::inRandomOrder()->first();
            $email = 'restaurant@'.Str::slug($restaurantName).'.com';

            $user = User::factory([
                'first_name' => 'Restaurant',
                'partner_referral_id' => $partner?->id,
                'email' => $email,
            ])
                ->has(Restaurant::factory([
                    'restaurant_name' => $restaurantName,
                ]))
                ->create();

            $user->assignRole('restaurant');

            Referral::create([
                'referrer_id' => $partner?->user->id,
                'user_id' => $user->id,
                'email' => $email,
                'secured_at' => now(),
                'type' => 'restaurant',
                'referrer_type' => 'partner',
            ]);
        });
    }
}

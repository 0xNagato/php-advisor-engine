<?php

namespace Database\Seeders;

use App\Enums\RestaurantStatus;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

ini_set('max_execution_time', 0); // 0 = Unlimited
ini_set('memory_limit', '5G');

class RestaurantProdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = collect([
            'miami' => [
                'Papi Steak',
                'Swan',
                'KOMODO',
                'Call Me Gaby',
                'Sexy Fish',
                'Standard Hotel',
                'RAOs',
            ],
            'ibiza' => [
                'Jondal',
                'Amante',
                'Cotton Beach Club',
                'Sublimotion',
                'Bambuddha',
                'Blue Marlin',
                'La Escollera',
                'Casa Maca',
                'Nobu Hotel Ibiza Bay',
                'Heart Ibiza',
                'Sa Capella',
            ],
        ]);

        $partnerUser = User::query()->create([
            'first_name' => 'PRIMA',
            'last_name' => 'Partner',
            'phone' => '+16473823326',
            'email' => 'partner@primavip.co',
            'password' => bcrypt('demo2024'),
        ])->create();

        $partner = Partner::query()->create([
            'user_id' => $partnerUser->id,
            'percentage' => 20,
        ])->create();

        $partnerUser->assignRole('partner');

        foreach ($regions as $region => $restaurants) {
            foreach ($restaurants as $restaurantName) {
                $email = 'restaurant@'.Str::slug($restaurantName).'-'.$region.'.com';

                $user = User::factory([
                    'first_name' => 'Restaurant',
                    'last_name' => $restaurantName,
                    'partner_referral_id' => $partner->id,
                    'email' => $email,
                ])
                    ->has(Restaurant::factory([
                        'restaurant_name' => $restaurantName,
                        'status' => RestaurantStatus::PENDING,
                        'region' => $region,
                    ]))
                    ->create();

                $user->assignRole('restaurant');

                Referral::query()->create([
                    'referrer_id' => $partner->user->id,
                    'user_id' => $user->id,
                    'email' => $email,
                    'secured_at' => now(),
                    'type' => 'restaurant',
                    'referrer_type' => 'partner',
                ]);
            }
        }
    }
}

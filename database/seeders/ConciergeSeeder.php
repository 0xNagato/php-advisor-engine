<?php

namespace Database\Seeders;

use App\Models\Concierge;
use App\Models\ConciergeReferral;
use App\Models\User;
use Illuminate\Database\Seeder;
use Random\RandomException;

class ConciergeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws RandomException
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

        $hotelNames->each(function ($hotelName) {
            $shouldAssignReferralId = random_int(0, 1) <= 0.5;
            $conciergeId = $shouldAssignReferralId ? Concierge::pluck('id')->random() : null;

            $email = 'concierge@'.str_replace(' ', '', strtolower($hotelName)).'.com';

            $user = User::factory([
                'first_name' => 'Concierge',
                'email' => $email,
                'concierge_referral_id' => $conciergeId,
            ])
                ->has(Concierge::factory([
                    'hotel_name' => $hotelName,
                ]))
                ->create();

            if ($shouldAssignReferralId) {
                ConciergeReferral::create([
                    'concierge_id' => $conciergeId,
                    'user_id' => $user->id,
                    'email' => $email,
                    'secured_at' => now(),
                ]);
            }

            $user->assignRole('concierge');
        });
    }
}

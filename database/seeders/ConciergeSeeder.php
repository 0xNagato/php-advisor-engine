<?php

namespace Database\Seeders;

use App\Models\Concierge;
use App\Models\Referral;
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
            $concierge = $shouldAssignReferralId ? Concierge::with('user')->get()->random() : null;

            $email = 'concierge@' . str_replace(' ', '', strtolower($hotelName)) . '.com';

            $user = User::factory([
                'first_name' => 'Concierge',
                'email' => $email,
                'concierge_referral_id' => $concierge->id ?? null,
            ])
                ->has(Concierge::factory([
                    'hotel_name' => $hotelName,
                ]))
                ->create();

            if ($shouldAssignReferralId) {
                Referral::create([
                    'referrer_id' => $concierge?->user->id,
                    'user_id' => $user->id,
                    'email' => $email,
                    'secured_at' => now(),
                ]);
            }

            $user->assignRole('concierge');
        });
    }
}

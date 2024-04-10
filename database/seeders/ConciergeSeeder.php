<?php

namespace Database\Seeders;

use App\Models\Concierge;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
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
            $partner = $shouldAssignReferralId ? Concierge::with('user')->get()->random() : Partner::inRandomOrder()->first();

            $email = 'concierge@' . Str::slug($hotelName) . '.com';

            $user = User::factory([
                'first_name' => 'Concierge',
                'email' => $email,
                'concierge_referral_id' => $partner->user->hasRole('concierge') ? $partner->id : null,
                'partner_referral_id' => $partner->user->hasRole('partner') ? $partner->id : null,
            ])
                ->has(Concierge::factory([
                    'hotel_name' => $hotelName,
                ]))
                ->create();


            Referral::create([
                'referrer_id' => $partner->user->id,
                'user_id' => $user->id,
                'email' => $email,
                'secured_at' => now(),
                'type' => 'concierge',
                'referrer_type' => $partner->user->hasRole('concierge') ? 'concierge' : 'partner',
            ]);

            $user->assignRole('concierge');
        });
    }
}

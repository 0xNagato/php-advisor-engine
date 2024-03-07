<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // lets start by creating 10 partners with user accounts and creating partner accounts and setting the user role to partner
        $users = User::factory(10)->create(['first_name' => 'Partner']);

        $users->each(function ($user) {
            Partner::factory()->create(['user_id' => $user->id]);
            $user->assignRole('partner');
        });

        // now each partner should randomly refer to 1-5 concierge or restaurant users
        $partners = Partner::all();
        $users = User::role(['restaurant', 'concierge'])->inRandomOrder()->take(20);
        $users->each(function ($user) use ($partners) {
            $partner = $partners->random();
            $user->partner_referral_id = $partner->id;
            $user->save();
        });
    }
}

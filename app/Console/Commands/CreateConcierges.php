<?php

namespace App\Console\Commands;

use App\Models\Concierge;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateConcierges extends Command
{
    protected $signature = 'demo:create-concierges';

    protected $description = 'Create or update concierge accounts for Andrew, Alex, and Kevin';

    public function handle()
    {
        $concierges = [
            ['first_name' => 'Andrew', 'email' => 'andrewconcierge@primavip.co'],
            ['first_name' => 'Alex', 'email' => 'alexconcierge@primavip.co'],
            ['first_name' => 'Kevin', 'email' => 'kevinconcierge@primavip.co'],
        ];

        foreach ($concierges as $conciergeData) {
            $user = User::firstOrCreate(
                ['email' => $conciergeData['email']],
                [
                    'first_name' => $conciergeData['first_name'],
                    'last_name' => 'Concierge',
                    'password' => Hash::make('password'),
                    'partner_referral_id' => Partner::query()->inRandomOrder()->first()->id,
                    'email_verified_at' => now(),
                ]
            );

            $concierge = Concierge::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'hotel_name' => "{$conciergeData['first_name']}'s Hotel",
                    'secured_at' => now(),
                ]
            );

            // Update secured_at if it's null (for existing concierges)
            if ($concierge->secured_at === null) {
                $concierge->update(['secured_at' => now()]);
            }

            $user->assignRole('concierge');

            $this->info("Concierge account created/updated for {$conciergeData['first_name']} Concierge");
        }

        $this->info('Concierge accounts created/updated successfully.');
    }
}

<?php

namespace App\Console\Commands;

use App\Actions\Booking\GenerateDemoBookings;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Console\Command;

class CreateConciergesAndBookings extends Command
{
    protected $signature = 'demo:create-concierges-and-bookings';

    protected $description = 'Create concierge accounts for Andrew, Alex, and Kevin, and generate 20 bookings each for the past 10 days';

    public function handle()
    {
        $this->createConcierges();
        $this->createBookings();

        $this->info('Concierge accounts created and bookings generated successfully.');
    }

    private function createConcierges()
    {
        $concierges = [
            ['first_name' => 'Andrew', 'email' => 'andrewconcierge@primavip.co'],
            ['first_name' => 'Alex', 'email' => 'alexconcierge@primavip.co'],
            ['first_name' => 'Kevin', 'email' => 'kevinconcierge@primavip.co'],
        ];

        foreach ($concierges as $conciergeData) {
            $existingUser = User::query()->where('email', $conciergeData['email'])->first();

            if ($existingUser) {
                $this->info("Concierge account already exists for {$conciergeData['first_name']} Concierge");

                continue;
            }

            $user = User::factory([
                'first_name' => $conciergeData['first_name'],
                'last_name' => 'Concierge',
                'email' => $conciergeData['email'],
                'password' => bcrypt('password'),
                'partner_referral_id' => Partner::query()->inRandomOrder()->first()->id,
            ])->create();

            Concierge::factory([
                'hotel_name' => "{$conciergeData['first_name']}'s Hotel",
            ])->for($user)->create();

            $user->assignRole('concierge');

            $this->info("Concierge account created for {$conciergeData['first_name']} Concierge");
        }
    }

    private function createBookings()
    {
        $concierges = Concierge::with('user')->whereIn('user_id', User::query()->whereIn('email', [
            'andrewconcierge@primavip.co',
            'alexconcierge@primavip.co',
            'kevinconcierge@primavip.co',
        ])->pluck('id'))->get();

        $generateDemoBookings = new GenerateDemoBookings;

        foreach ($concierges as $concierge) {
            $this->info("Generating bookings for {$concierge->user->first_name} Concierge");

            $startDate = now()->subDays(10);
            $endDate = now();

            $existingBookingsCount = Booking::query()->where('concierge_id', $concierge->id)
                ->whereBetween('booking_at', [$startDate, $endDate])
                ->count();

            if ($existingBookingsCount >= 20) {
                $this->info("20 bookings already exist for {$concierge->user->first_name} Concierge");

                continue;
            }

            $bookingsToCreate = 20 - $existingBookingsCount;
            $generateDemoBookings->generateBookingsForConcierge($concierge, $startDate, $endDate, $bookingsToCreate);

            $this->info("{$bookingsToCreate} bookings created for {$concierge->user->first_name} Concierge");
        }
    }
}

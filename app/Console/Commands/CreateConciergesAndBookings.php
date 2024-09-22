<?php

namespace App\Console\Commands;

use App\Actions\Booking\GenerateDemoBookings;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Hash;

class CreateConciergesAndBookings extends Command
{
    protected $signature = 'demo:create-concierges-and-bookings';

    protected $description = 'Create or update concierge accounts for Andrew, Alex, and Kevin, and generate 20 bookings each for the past 10 days';

    public function handle()
    {
        $this->createOrUpdateConcierges();
        $this->createBookings();

        $this->info('Concierge accounts updated and bookings generated successfully.');
    }

    private function createOrUpdateConcierges()
    {
        $concierges = [
            ['first_name' => 'Andrew', 'email' => 'andrewconcierge@primavip.co'],
            ['first_name' => 'Alex', 'email' => 'alexconcierge@primavip.co'],
            ['first_name' => 'Kevin', 'email' => 'kevinconcierge@primavip.co'],
        ];

        foreach ($concierges as $conciergeData) {
            $user = User::query()->firstOrCreate(['email' => $conciergeData['email']], [
                'first_name' => $conciergeData['first_name'],
                'last_name' => 'Concierge',
                'password' => Hash::make('password'),
                'partner_referral_id' => Partner::query()->inRandomOrder()->first()->id,
                'email_verified_at' => now(),
            ]);

            $concierge = Concierge::query()->firstOrCreate(['user_id' => $user->id], [
                'hotel_name' => "{$conciergeData['first_name']}'s Hotel",
                'secured_at' => now(),  // Add this line
            ]);

            // Update secured_at if it's null (for existing concierges)
            if ($concierge->secured_at === null) {
                $concierge->update(['secured_at' => now()]);
            }

            $user->assignRole('concierge');

            $this->info("Concierge account created/updated for {$conciergeData['first_name']} Concierge");
        }
    }

    private function createBookings()
    {
        $concierges = Concierge::with('user')->whereHas('user', function (Builder $query) {
            $query->whereIn('email', [
                'andrewconcierge@primavip.co',
                'alexconcierge@primavip.co',
                'kevinconcierge@primavip.co',
            ]);
        })->get();

        $this->info('Found '.$concierges->count().' concierges');

        $generateDemoBookings = new GenerateDemoBookings;

        foreach ($concierges as $concierge) {
            $this->info("Generating bookings for {$concierge->user->first_name} Concierge");

            $startDate = now()->subDays(10);
            $endDate = now();

            $existingBookingsCount = Booking::query()->where('concierge_id', $concierge->id)
                ->whereBetween('booking_at', [$startDate, $endDate])
                ->count();

            $this->info("Existing bookings count: {$existingBookingsCount}");

            if ($existingBookingsCount >= 20) {
                $this->info("20 bookings already exist for {$concierge->user->first_name} Concierge");

                continue;
            }

            $bookingsToCreate = 20 - $existingBookingsCount;
            $this->info("Attempting to create {$bookingsToCreate} bookings");

            $generateDemoBookings->generateBookingsForConcierge($concierge, $startDate, $endDate, $bookingsToCreate);

            $newBookingsCount = Booking::query()->where('concierge_id', $concierge->id)
                ->whereBetween('booking_at', [$startDate, $endDate])
                ->count();

            $this->info("New bookings count: {$newBookingsCount}");
            $this->info('Total bookings created: '.($newBookingsCount - $existingBookingsCount));
        }
    }
}

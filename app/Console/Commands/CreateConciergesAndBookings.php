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
use Throwable;

class CreateConciergesAndBookings extends Command
{
    protected $signature = 'demo:create-concierges-and-bookings {names? : Comma-separated list of concierge names}';

    protected $description = 'Create or update concierge accounts for given names and generate 20 bookings each for the past 10 days';

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $names = $this->argument('names') ? explode(',', $this->argument('names')) : ['Andrew', 'Alex', 'Kevin'];
        $this->createOrUpdateConcierges($names);
        $this->createBookings($names);

        $this->info('Concierge accounts updated and bookings generated successfully.');
    }

    private function createOrUpdateConcierges(array $names): void
    {
        foreach ($names as $name) {
            $name = trim($name);
            $email = strtolower($name).'concierge@primavip.co';

            $user = User::query()->firstOrCreate(['email' => $email], [
                'first_name' => $name,
                'last_name' => 'Concierge',
                'password' => Hash::make('password'),
                'partner_referral_id' => Partner::query()->inRandomOrder()->first()->id,
                'email_verified_at' => now(),
            ]);

            $concierge = Concierge::query()->firstOrCreate(['user_id' => $user->id], [
                'hotel_name' => "$name's Hotel",
                'secured_at' => now(),
            ]);

            // Update secured_at if it's null (for existing concierges)
            if ($concierge->secured_at === null) {
                $concierge->update(['secured_at' => now()]);
            }

            $user->assignRole('concierge');

            $this->info("Concierge account created/updated for $name Concierge");
        }
    }

    /**
     * @throws Throwable
     */
    private function createBookings(array $names): void
    {
        $concierges = Concierge::with('user')->whereHas('user', function (Builder $query) use ($names) {
            $query->whereIn('email', array_map(fn ($name) => strtolower(trim($name)).'concierge@primavip.co', $names));
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

            $this->info("Existing bookings count: $existingBookingsCount");

            if ($existingBookingsCount >= 20) {
                $this->info("20 bookings already exist for {$concierge->user->first_name} Concierge");

                continue;
            }

            $bookingsToCreate = 20 - $existingBookingsCount;
            $this->info("Attempting to create $bookingsToCreate bookings");

            $generateDemoBookings->generateBookingsForConcierge($concierge, $startDate, $endDate, $bookingsToCreate);

            $newBookingsCount = Booking::query()->where('concierge_id', $concierge->id)
                ->whereBetween('booking_at', [$startDate, $endDate])
                ->count();

            $this->info("New bookings count: $newBookingsCount");
            $this->info('Total bookings created: '.($newBookingsCount - $existingBookingsCount));
        }
    }
}

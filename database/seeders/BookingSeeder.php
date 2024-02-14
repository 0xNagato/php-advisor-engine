<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Random\RandomException;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws RandomException
     */
    public function run(): void
    {
        // Get all schedules and randomize them
        $schedules = Schedule::all()->shuffle();

        // Get all concierges
        $concierges = Concierge::all();

        // For each schedule
        foreach ($schedules as $schedule) {
            // Generate a random booking rate between 20-70%
            $bookingRate = random_int(20, 70) / 100;

            // Calculate the amount bookings to be created
            $bookingsCount = (int) ($schedule->computed_available_tables * $bookingRate);

            // For each booking to be created
            for ($i = 0; $i < $bookingsCount; $i++) {
                // Create a new booking using a factory
                Booking::factory()->create([
                    'schedule_id' => $schedule->id,
                    'concierge_id' => $concierges->random()->id,
                ]);
            }
        }
    }
}

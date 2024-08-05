<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $venues = Venue::all();

        $venues->each(function ($venue) {
            $startTime = Carbon::createFromTime(12, 0, 0);
            $endTime = Carbon::createFromTime(24, 0, 0);

            for ($time = $startTime; $time->lessThan($endTime); $time->addMinutes(30)) {
                Schedule::factory()->create([
                    'start_time' => $time->format('H:i:s'),
                    'end_time' => $time->copy()->addMinutes(30)->format('H:i:s'),
                    'venue_id' => $venue->id,
                ]);
            }
        });
    }
}

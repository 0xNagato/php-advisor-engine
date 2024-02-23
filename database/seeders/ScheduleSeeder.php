<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::all();

        $restaurants->each(function ($restaurant) {
            $startTime = Carbon::createFromTime(17, 0, 0); // 5pm
            $endTime = Carbon::createFromTime(22, 30, 0); // 10:30pm

            for ($time = $startTime; $time->lessThan($endTime); $time->addMinutes(30)) {
                Schedule::factory()->create([
                    'start_time' => $time->format('H:i:s'),
                    'end_time' => $time->copy()->addMinutes(30)->format('H:i:s'),
                    'restaurant_id' => $restaurant->id,
                ]);
            }
        });
    }
}

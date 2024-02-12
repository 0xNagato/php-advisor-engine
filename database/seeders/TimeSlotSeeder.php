<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\TimeSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::all();

        $restaurants->each(function ($restaurant) {
            for ($day = 0; $day < 7; $day++) {
                $date = Carbon::now()->addDays($day);
                $startTime = Carbon::createFromTime(17, 0, 0); // 5pm
                $endTime = Carbon::createFromTime(20, 30, 0); // 8:30pm

                for ($time = $startTime; $time->lessThan($endTime); $time->addMinutes(30)) {
                    TimeSlot::factory()->create([
                        'date' => $date->format('Y-m-d'),
                        'start_time' => $time->format('H:i:s'),
                        'end_time' => $time->copy()->addMinutes(30)->format('H:i:s'),
                        'restaurant_id' => $restaurant->id,
                    ]);
                }
            }
        });
    }
}

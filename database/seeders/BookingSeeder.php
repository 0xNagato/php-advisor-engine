<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\TimeSlot;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $concierges = Concierge::all();
        $timeSlots = TimeSlot::all();

        $timeSlotsCount = $timeSlots->count();
        $eightyPercentTimeSlots = (int)($timeSlotsCount * 0.8);

        $timeSlots = $timeSlots->shuffle()->slice(0, $eightyPercentTimeSlots);

        $timeSlots->each(function ($timeSlot) use ($concierges) {
            Booking::factory()->create([
                'time_slot_id' => $timeSlot->id,
                'concierge_id' => $concierges->random()->id,
            ]);
        });
    }
}

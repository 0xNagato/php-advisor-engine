<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Models\ScheduleTemplate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddSpecialPartySize extends Command
{
    protected $signature = 'restaurants:add-special-party-size';

    protected $description = 'Add special party size to all restaurants and generate schedule templates';

    public function handle(): int
    {
        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            $partySizes = $restaurant->party_sizes;
            if (! isset($partySizes['Special Request'])) {
                $partySizes['Special Request'] = 0;
                $restaurant->party_sizes = $partySizes;
                $restaurant->save();
                $this->generateScheduleTemplates($restaurant);
                $this->info("Added special party size to restaurant: $restaurant->restaurant_name");
            }
        }

        $this->info('Special party size added to all restaurants and schedule templates generated.');

        return Command::SUCCESS;
    }

    protected function generateScheduleTemplates(Restaurant $restaurant): void
    {
        $existingTemplates = ScheduleTemplate::where('restaurant_id', $restaurant->id)
            ->where('party_size', 'Special Request')
            ->get()
            ->pluck('day_of_week', 'start_time')
            ->toArray();

        $schedulesData = [];
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $partySize = 0;

        foreach ($daysOfWeek as $dayOfWeek) {
            $startTime = Carbon::createFromTime(Restaurant::DEFAULT_START_HOUR);
            $endTime = Carbon::createFromTime(Restaurant::DEFAULT_END_HOUR, 30);

            while ($startTime->lessThanOrEqualTo($endTime)) {
                $startTimeFormatted = $startTime->format('H:i:s');

                if (! isset($existingTemplates[$startTimeFormatted]) || $existingTemplates[$startTimeFormatted] !== $dayOfWeek) {
                    $isAvailable = $startTime->hour >= Restaurant::DEFAULT_START_HOUR && ($startTime->hour < Restaurant::DEFAULT_END_HOUR || ($startTime->hour === Restaurant::DEFAULT_END_HOUR && $startTime->minute < 30));

                    $timeSlotStart = clone $startTime;

                    $schedulesData[] = [
                        'restaurant_id' => $restaurant->id,
                        'start_time' => $timeSlotStart->format('H:i:s'),
                        'end_time' => $timeSlotStart->addMinutes(30)->format('H:i:s'),
                        'is_available' => $isAvailable,
                        'prime_time' => $isAvailable,
                        'available_tables' => $isAvailable ? Restaurant::DEFAULT_TABLES : 0,
                        'day_of_week' => $dayOfWeek,
                        'party_size' => $partySize,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $startTime->addMinutes(30);
            }
        }

        if (filled($schedulesData)) {
            $restaurant->scheduleTemplates()->insert($schedulesData);
        }
    }
}

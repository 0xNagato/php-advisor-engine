<?php

namespace App\Console\Commands;

use App\Models\ScheduleTemplate;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddSpecialPartySize extends Command
{
    protected $signature = 'venues:add-special-party-size';

    protected $description = 'Add special party size to all venues and generate schedule templates';

    public function handle(): int
    {
        $venues = Venue::all();

        foreach ($venues as $venue) {
            $partySizes = $venue->party_sizes;
            if (! isset($partySizes['Special Request'])) {
                $partySizes['Special Request'] = 0;
                $venue->party_sizes = $partySizes;
                $venue->save();
                $this->generateScheduleTemplates($venue);
                $this->info("Added special party size to venue: $venue->name");
            }
        }

        $this->info('Special party size added to all venues and schedule templates generated.');

        return Command::SUCCESS;
    }

    protected function generateScheduleTemplates(Venue $venue): void
    {
        $existingTemplates = ScheduleTemplate::query()->where('venue_id', $venue->id)
            ->where('party_size', 'Special Request')
            ->get()
            ->pluck('day_of_week', 'start_time')
            ->toArray();

        $schedulesData = [];
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $partySize = 0;

        foreach ($daysOfWeek as $dayOfWeek) {
            $startTime = Carbon::createFromTime(Venue::DEFAULT_START_HOUR);
            $endTime = Carbon::createFromTime(Venue::DEFAULT_END_HOUR, 30);

            while ($startTime->lessThanOrEqualTo($endTime)) {
                $startTimeFormatted = $startTime->format('H:i:s');

                if (! isset($existingTemplates[$startTimeFormatted]) || $existingTemplates[$startTimeFormatted] !== $dayOfWeek) {
                    $isAvailable = $startTime->hour >= Venue::DEFAULT_START_HOUR && ($startTime->hour < Venue::DEFAULT_END_HOUR || ($startTime->hour === Venue::DEFAULT_END_HOUR && $startTime->minute < 30));

                    $timeSlotStart = clone $startTime;

                    $schedulesData[] = [
                        'venue_id' => $venue->id,
                        'start_time' => $timeSlotStart->format('H:i:s'),
                        'end_time' => $timeSlotStart->addMinutes(30)->format('H:i:s'),
                        'is_available' => $isAvailable,
                        'prime_time' => $isAvailable,
                        'available_tables' => $isAvailable ? Venue::DEFAULT_TABLES : 0,
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
            $venue->scheduleTemplates()->insert($schedulesData);
        }
    }
}

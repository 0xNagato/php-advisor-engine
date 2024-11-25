<?php

namespace App\Services;

use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class VenueScheduleService
{
    public function updateSchedule(Venue $venue, array $schedule): void
    {
        foreach ($schedule as $dayOfWeek => $daySchedule) {
            $venue->scheduleTemplates()
                ->where('day_of_week', $dayOfWeek)
                ->chunk(200, function (Collection $templates) use ($daySchedule) {
                    foreach ($templates as $template) {
                        $startTime = Carbon::createFromFormat('H:i:s', $template->start_time);
                        $isPrimeTime = false;

                        if ($daySchedule['is_open'] && filled($daySchedule['prime_slots'])) {
                            foreach ($daySchedule['prime_slots'] as $slot) {
                                $slotStart = Carbon::createFromFormat('H:i', $slot['start']);
                                $slotEnd = Carbon::createFromFormat('H:i', $slot['end']);

                                if ($startTime->between($slotStart, $slotEnd)) {
                                    $isPrimeTime = true;
                                    break;
                                }
                            }
                        }

                        $template->update([
                            'is_prime_time' => $isPrimeTime,
                        ]);
                    }
                });
        }
    }

    public function createDefaultScheduleTemplates(Venue $venue, array $schedule): void
    {
        $schedulesData = [];

        foreach ($schedule as $dayOfWeek => $dayData) {
            if (! $dayData['is_open']) {
                continue;
            }

            $startTime = Carbon::createFromFormat('H:i', $dayData['open_time']);
            $endTime = Carbon::createFromFormat('H:i', $dayData['close_time']);

            while ($startTime->lessThanOrEqualTo($endTime)) {
                $timeSlotStart = clone $startTime;
                $currentTime = $timeSlotStart->format('H:i:s');

                $isPrimeTime = false;
                if (filled($dayData['prime_slots'])) {
                    foreach ($dayData['prime_slots'] as $slot) {
                        $slotStart = Carbon::createFromFormat('H:i', $slot['start']);
                        $slotEnd = Carbon::createFromFormat('H:i', $slot['end']);

                        if ($timeSlotStart->between($slotStart, $slotEnd)) {
                            $isPrimeTime = true;
                            break;
                        }
                    }
                }

                foreach ($venue->party_sizes as $partySize => $size) {
                    $schedulesData[] = [
                        'venue_id' => $venue->id,
                        'start_time' => $currentTime,
                        'end_time' => $timeSlotStart->addMinutes(30)->format('H:i:s'),
                        'is_available' => true,
                        'prime_time' => $isPrimeTime,
                        'available_tables' => Venue::DEFAULT_TABLES,
                        'day_of_week' => $dayOfWeek,
                        'party_size' => $size,
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

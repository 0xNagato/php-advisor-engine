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
}

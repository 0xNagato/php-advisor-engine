<?php

namespace App\Services;

use App\Data\Venue\SaveReservationHoursData;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VenueScheduleService
{
    public function __construct(
        private readonly ReservationHoursService $reservationHoursService
    ) {}

    public function updateSchedule(Venue $venue, array $schedule): void
    {
        DB::transaction(function () use ($venue, $schedule) {
            // First update the open days and hours using ReservationHoursService
            $selectedDays = [];
            $startTimes = [];
            $endTimes = [];

            foreach ($schedule as $day => $daySchedule) {
                $selectedDays[$day] = $daySchedule['is_open'];
                if ($daySchedule['is_open']) {
                    $startTimes[$day] = $daySchedule['open_time'];
                    $endTimes[$day] = $daySchedule['close_time'];
                }
            }

            // Use the service to save hours
            $data = new SaveReservationHoursData(
                venue: $venue,
                selectedDays: $selectedDays,
                startTimes: $startTimes,
                endTimes: $endTimes
            );

            $this->reservationHoursService->saveHours($data);

            // Then handle prime time slots separately
            foreach ($schedule as $dayOfWeek => $daySchedule) {
                $venue->scheduleTemplates()
                    ->where('day_of_week', $dayOfWeek)
                    ->chunk(200, function ($templates) use ($daySchedule) {
                        foreach ($templates as $template) {
                            $startTime = Carbon::createFromFormat('H:i:s', $template->start_time);
                            $isPrimeTime = false;

                            if ($daySchedule['is_open'] && ! empty($daySchedule['prime_slots'])) {
                                foreach ($daySchedule['prime_slots'] as $slot) {
                                    $slotStart = Carbon::createFromFormat('H:i', $slot['start']);
                                    $slotEnd = Carbon::createFromFormat('H:i', $slot['end']);

                                    if ($startTime->format('H:i') >= $slotStart->format('H:i') &&
                                        $startTime->format('H:i') <= $slotEnd->format('H:i')) {
                                        $isPrimeTime = true;
                                        break;
                                    }
                                }
                            }

                            $template->update([
                                'prime_time' => $isPrimeTime,
                                'available_tables' => $daySchedule['is_open'] ? Venue::DEFAULT_TABLES : 0,
                            ]);
                        }
                    });
            }
        });
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
                if (! empty($dayData['prime_slots'])) {
                    foreach ($dayData['prime_slots'] as $slot) {
                        $slotStart = Carbon::createFromFormat('H:i', $slot['start']);
                        $slotEnd = Carbon::createFromFormat('H:i', $slot['end']);

                        if ($timeSlotStart->format('H:i') >= $slotStart->format('H:i') &&
                            $timeSlotStart->format('H:i') <= $slotEnd->format('H:i')) {
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

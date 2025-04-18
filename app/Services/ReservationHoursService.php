<?php

namespace App\Services;

use App\Data\Venue\LoadReservationHoursBlockData;
use App\Data\Venue\SaveReservationHoursBlockData;
use App\Models\Venue;

class ReservationHoursService
{
    protected array $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function loadHours(Venue $venue): LoadReservationHoursBlockData
    {
        $openingHours = [];
        $selectedDays = [];

        foreach ($this->daysOfWeek as $day) {
            $schedules = $venue->scheduleTemplates()->where('day_of_week', $day)->orderBy('start_time')->get();

            $selectedDays[$day] = ! $schedules->every(fn ($schedule) => $schedule->is_available === false);

            if ($selectedDays[$day]) {
                $openingHours[$day] = $this->groupProximityBlocks(
                    $schedules->filter(fn ($schedule) => $schedule->is_available)
                        ->map(fn ($schedule) => [
                            'start_time' => $schedule->start_time,
                            'end_time' => $schedule->end_time,
                        ])
                        ->toArray()
                );
            } else {
                $openingHours[$day] = [];
            }
        }

        return new LoadReservationHoursBlockData(openingHours: $openingHours, selectedDays: $selectedDays);
    }

    public function saveHours(SaveReservationHoursBlockData $data): void
    {
        $openDays = [];

        foreach ($this->daysOfWeek as $day) {
            $isActive = $data->selectedDays[$day];
            $openDays[$day] = $isActive ? 'open' : 'closed';

            if (! $isActive) {
                $data->venue->scheduleTemplates()
                    ->where('day_of_week', $day)
                    ->update(['is_available' => 0]);

                continue;
            }

            $schedules = $data->venue->scheduleTemplates()->where('day_of_week', $day)->get();

            $timeBlocks = $this->groupProximityBlocks($data->openingHours[$day] ?? []);

            $schedules->each(function ($schedule) use ($timeBlocks) {
                $isAvailable = $this->isWithinAnyRange(
                    $schedule->start_time,
                    $schedule->end_time,
                    $timeBlocks
                );

                $schedule->update(['is_available' => $isAvailable]);
            });
        }

        $data->venue->open_days = $openDays;
        $data->venue->save();
    }

    private function groupProximityBlocks(array $blocks): array
    {
        if (blank($blocks)) {
            return [];
        }

        usort($blocks, fn ($a, $b) => strcmp((string) $a['start_time'], (string) $b['start_time']));

        $mergedBlocks = [];
        $currentBlock = null;

        foreach ($blocks as $block) {
            if (! $currentBlock) {
                $currentBlock = $block;

                continue;
            }

            if ($currentBlock['end_time'] >= $block['start_time']) {
                $currentBlock['end_time'] = max($currentBlock['end_time'], $block['end_time']);
            } else {
                $mergedBlocks[] = $currentBlock;
                $currentBlock = $block;
            }
        }

        if ($currentBlock) {
            $mergedBlocks[] = $currentBlock;
        }

        return $mergedBlocks;
    }

    private function isWithinAnyRange(string $scheduleStart, string $scheduleEnd, array $timeBlocks): bool
    {
        if ($scheduleEnd === '00:00:00') {
            $scheduleEnd = '24:00:00';
        }

        foreach ($timeBlocks as $block) {
            $blockStart = $block['start_time'];
            $blockEnd = $block['end_time'];

            if ($blockEnd === '00:00:00') {
                $blockEnd = '24:00:00';
            }

            if ($scheduleStart >= $blockStart && $scheduleEnd <= $blockEnd) {
                return true;
            }
        }

        return false;
    }
}

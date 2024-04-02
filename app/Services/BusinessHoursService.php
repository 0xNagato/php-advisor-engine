<?php

namespace App\Services;

use App\Data\Restaurant\LoadBusinessHoursData;
use App\Data\Restaurant\SaveBusinessHoursData;
use App\Models\Restaurant;

class BusinessHoursService
{
    protected array $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function loadBusinessHours(Restaurant $restaurant): LoadBusinessHoursData
    {
        $startTimes = [];
        $endTimes = [];
        $selectedDays = [];

        foreach ($this->daysOfWeek as $day) {
            $schedules = $restaurant->schedules()->where('day_of_week', $day)->orderBy('start_time')->get();

            $selectedDays[$day] = !$schedules->every(fn($schedule) => $schedule->is_available === false);
            if ($selectedDays[$day]) {
                $startTimes[$day] = $schedules->first(fn($schedule) => $schedule->is_available)?->start_time;
                $endTimes[$day] = $schedules->last(fn($schedule) => $schedule->is_available)?->end_time;
            }
        }

        return new LoadBusinessHoursData(startTimes: $startTimes, endTimes: $endTimes, selectedDays: $selectedDays);
    }

    public function saveBusinessHours(SaveBusinessHoursData $data): void
    {
        foreach ($this->daysOfWeek as $day) {
            $schedules = $data->restaurant->schedules()->where('day_of_week', $day)->get();

            foreach ($schedules as $schedule) {
                $schedule->update(['is_available' => $data->selectedDays[$day] && $schedule->start_time >= $data->startTimes[$day] && $schedule->start_time < $data->endTimes[$day]]);
            }
        }
    }
}

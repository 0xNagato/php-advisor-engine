<?php

namespace App\Services;

use App\Data\Restaurant\LoadReservationHoursData;
use App\Data\Restaurant\SaveReservationHoursData;
use App\Models\Restaurant;

class ReservationHoursService
{
    protected array $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function loadHours(Restaurant $restaurant): LoadReservationHoursData
    {
        $startTimes = [];
        $endTimes = [];
        $selectedDays = [];

        foreach ($this->daysOfWeek as $day) {
            $schedules = $restaurant->schedules()->where('day_of_week', $day)->orderBy('start_time')->get();

            $selectedDays[$day] = !$schedules->every(fn($schedule) => $schedule->is_available === false);
            if ($selectedDays[$day]) {
                $startTimes[$day] = $schedules->first(fn($schedule) => $schedule->is_available)?->start_time;
                $endTimes[$day] = $schedules->last(fn($schedule) => $schedule->is_available)?->start_time;
            }
        }

        return new LoadReservationHoursData(startTimes: $startTimes, endTimes: $endTimes, selectedDays: $selectedDays);
    }

    public function saveHours(SaveReservationHoursData $data): void
    {
        foreach ($this->daysOfWeek as $day) {
            $schedules = $data->restaurant->schedules()->where('day_of_week', $day)->get();

            foreach ($schedules as $schedule) {
                $schedule->update(['is_available' => $data->selectedDays[$day] && $schedule->start_time >= $data->startTimes[$day] && $schedule->start_time <= $data->endTimes[$day]]);
            }
        }
    }
}

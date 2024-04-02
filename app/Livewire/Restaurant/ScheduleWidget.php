<?php

namespace App\Livewire\Restaurant;

use App\Models\Restaurant;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class ScheduleWidget extends Widget
{
    protected static string $view = 'livewire.restaurant.schedule-widget';

    protected static bool $isLazy = false;

    public $schedules = [];
    protected Restaurant $restaurant;

    protected $listeners = ['business-hours-updated' => '$refresh'];

    public function updateTableAvailability($date, $time, $tables): void
    {
        $this->schedules[$date][$time] = $tables;
    }

    public function updated($propertyName, $value)
    {
        // Split the property name into schedules, date and time
        [$schedules, $date, $time] = explode('.', $propertyName);

        // Check if the keys exist in the schedules array
        if (isset($this->schedules[$date][$time])) {
            // Get the new number of tables
            $tables = $value;

            // Update the corresponding schedule in the database
            auth()->user()->restaurant->schedules()
                ->where('day_of_week', $date)
                ->where('start_time', $time)
                ->update(['available_tables' => $tables]);
        }
    }

    #[On('business-hours-updated')]
    public function update(): void
    {
        $this->mount();
    }

    public function mount(): void
    {
        $this->restaurant = auth()->user()->restaurant;
        $this->generateSchedules();
    }

    public function generateSchedules(): void
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOfWeek as $day) {
            $this->schedules[$day] = $this->generateTimes($day);
        }
    }

    public function generateTimes(string $day): array
    {
        $times = [];
        $schedules = $this->restaurant->schedules()->where('day_of_week', $day)->where('is_available', true)->get();

        foreach ($schedules as $schedule) {
            $times[$schedule->start_time] = $schedule->available_tables;
        }

        return $times;
    }
}

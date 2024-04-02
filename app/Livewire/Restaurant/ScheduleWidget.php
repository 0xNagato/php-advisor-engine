<?php

namespace App\Livewire\Restaurant;

use App\Models\Restaurant;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Validation\ValidationException;
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

    public function updated($propertyName, $value): void
    {
        // Split the property name into schedules, date and time
        [$schedules, $date, $time] = explode('.', $propertyName);
        // Check if the keys exist in the schedules array
        if (isset($this->schedules[$date][$time])) {
            try {
                $this->validate([
                    $propertyName => 'numeric|min:0|max:30',
                ]);
            } catch (ValidationException $e) {
                // Format the time to 12-hour format
                $formattedTime = Carbon::createFromFormat('H:i:s', $time)->format('g:ia');

                // Send a notification
                Notification::make()
                    ->title('Max tables (30) exceeded on ' . ucfirst($date) . ' at ' . $formattedTime)
                    ->danger()
                    ->send();

                $this->schedules[$date][$time] = 30;

                return;
            }


            if ($value === '') {
                return;
            }

            auth()->user()->restaurant->schedules()
                ->where('day_of_week', $date)
                ->where('start_time', $time)
                ->update(['available_tables' => $value]);

            Notification::make()
                ->title('Table availability updated successfully')
                ->success()
                ->send();
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

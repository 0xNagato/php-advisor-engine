<?php

namespace App\Livewire\Venue;

use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class TableAvailability extends Widget
{
    protected static string $view = 'livewire.venue.table-availability';

    protected static ?string $pollingInterval = null;

    public $schedules = [];

    protected $listeners = ['reservation-hours-updated' => '$refresh'];

    public function mount(): void
    {
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
        $schedules = auth()->user()->venue->scheduleTemplates()->where('day_of_week', $day)->where('is_available', true)->get();

        foreach ($schedules as $schedule) {
            // 0 is used for Special Requests, so we can skip it here
            if ($schedule->party_size === 0) {
                continue;
            }

            if (! isset($times[$schedule->start_time])) {
                $times[$schedule->start_time] = [];
                $times[$schedule->start_time]['prime_time'] = $schedule->prime_time;
            }
            $times[$schedule->start_time][$schedule->party_size] = $schedule->available_tables;
        }

        return $times;
    }

    public function saveTableAvailability(): void
    {
        $rules = [];

        // Generate validation rules for each available_tables value
        foreach ($this->schedules as $day => $times) {
            foreach ($times as $time => $partySizes) {
                foreach ($partySizes as $partySize => $availableTables) {
                    if ($partySize === 'prime_time') {
                        continue;
                    }

                    $rules["schedules.$day.$time.$partySize"] = ['numeric', 'max:30'];
                }
            }
        }

        $this->validate($rules);
        foreach ($this->schedules as $day => $times) {
            auth()->user()->venue->scheduleTemplates()
                ->where('day_of_week', $day)
                ->chunk(200, function ($schedules) use ($times) {
                    foreach ($schedules as $schedule) {
                        if (isset($times[$schedule->start_time])) {
                            $scheduleData = $times[$schedule->start_time];
                            $schedule->available_tables = $scheduleData[$schedule->party_size] ?? 0;
                            $schedule->prime_time = $scheduleData['prime_time'] ?? false;
                            $schedule->save();
                        }
                    }
                });
        }

        Notification::make()
            ->title('Table availability updated successfully')
            ->success()
            ->send();
    }

    #[On('reservation-hours-updated')]
    public function update(): void
    {
        $this->mount();
    }
}

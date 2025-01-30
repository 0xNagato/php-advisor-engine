<?php

namespace App\Livewire\Venue;

use App\Data\Venue\SaveReservationHoursData;
use App\Models\Venue;
use App\Services\ReservationHoursService;
use Carbon\Carbon;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class ReservationHoursWidget extends Widget
{
    use WithRateLimiting;

    protected static string $view = 'livewire.venue.open-hours-widget';

    protected static ?string $pollingInterval = null;

    public array $startTimes = [];

    public array $endTimes = [];

    public array $selectedDays = [];

    public array $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    protected Venue $venue;

    protected ReservationHoursService $reservationHoursService;

    public function boot(ReservationHoursService $reservationHoursService): void
    {
        $this->reservationHoursService = $reservationHoursService;
    }

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
        $data = $this->reservationHoursService->loadHours($this->venue);

        $this->startTimes = $data->startTimes;
        $this->endTimes = $data->endTimes;
        $this->selectedDays = $data->selectedDays;
    }

    public function updatedSelectedDays($value, $key): void
    {
        if ($value === true && ! isset($this->startTimes[$key], $this->endTimes[$key])) {
            $this->startTimes[$key] = Venue::DEFAULT_START_HOUR.':00:00';
            $this->endTimes[$key] = Venue::DEFAULT_END_HOUR.':00:00';
        }
    }

    public function updatedStartTimes($value, $key): void
    {
        $this->startTimes[$key] = Carbon::createFromFormat('H:i', substr((string) $value, 0, 5))->format('H:i:s');
    }

    public function updatedEndTimes($value, $key): void
    {
        $this->endTimes[$key] = Carbon::createFromFormat('H:i', substr((string) $value, 0, 5))->format('H:i:s');
    }

    public function saveHours(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title("Too many requests! Please wait another $exception->secondsUntilAvailable seconds.")
                ->danger()
                ->send();

            return;
        }

        foreach ($this->daysOfWeek as $day) {
            if ($this->selectedDays[$day]) {
                $this->validate([
                    'startTimes.'.$day => ['required', 'date_format:H:i:s', 'before:endTimes.'.$day],
                    'endTimes.'.$day => ['required', 'date_format:H:i:s', 'after:startTimes.'.$day],
                ]);

                $startTime = Carbon::createFromFormat('H:i', substr((string) $this->startTimes[$day], 0, 5))->format('H:i:s');
                $endTime = Carbon::createFromFormat('H:i', substr((string) $this->endTimes[$day], 0, 5))->format('H:i:s');

                $this->startTimes[$day] = $startTime;
                $this->endTimes[$day] = $endTime;
            }
        }

        $this->reservationHoursService->saveHours(new SaveReservationHoursData(
            venue: $this->venue,
            startTimes: $this->startTimes,
            endTimes: $this->endTimes,
            selectedDays: $this->selectedDays,
        ));

        $this->dispatch('reservation-hours-updated');

        Notification::make()
            ->title('Reservation hours saved successfully.')
            ->success()
            ->send();
    }
}

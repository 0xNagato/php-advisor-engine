<?php

namespace App\Livewire\Restaurant;

use App\Data\Restaurant\SaveReservationHoursData;
use App\Models\Restaurant;
use App\Services\ReservationHoursService;
use Carbon\Carbon;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class ReservationHoursWidget extends Widget
{
    use WithRateLimiting;

    protected static string $view = 'livewire.restaurant.open-hours-widget';

    protected static bool $isLazy = false;

    public array $startTimes = [];

    public array $endTimes = [];

    public array $selectedDays = [];

    public array $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];


    protected Restaurant $restaurant;
    protected ReservationHoursService $reservationHoursService;

    public function boot(ReservationHoursService $reservationHoursService): void
    {
        $this->restaurant = auth()->user()->restaurant;
        $this->reservationHoursService = $reservationHoursService;
    }

    public function mount(): void
    {
        $data = $this->reservationHoursService->loadHours($this->restaurant);

        $this->startTimes = $data->startTimes;
        $this->endTimes = $data->endTimes;
        $this->selectedDays = $data->selectedDays;
    }

    public function updatedSelectedDays($value, $key): void
    {
        if ($value === true && !isset($this->startTimes[$key], $this->endTimes[$key])) {
            $this->startTimes[$key] = '12:00:00';
            $this->endTimes[$key] = '22:00:00';
        }
    }

    public function updatedStartTimes($value, $key): void
    {
        $this->startTimes[$key] = Carbon::createFromFormat('H:i', substr($value, 0, 5))->format('H:i:s');
    }

    public function updatedEndTimes($value, $key): void
    {
        $this->endTimes[$key] = Carbon::createFromFormat('H:i', substr($value, 0, 5))->format('H:i:s');
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
                    'startTimes.' . $day => ['required', 'date_format:H:i:s', 'before:endTimes.' . $day],
                    'endTimes.' . $day => ['required', 'date_format:H:i:s', 'after:startTimes.' . $day],
                ]);

                $startTime = Carbon::createFromFormat('H:i', substr($this->startTimes[$day], 0, 5))->format('H:i:s');
                $endTime = Carbon::createFromFormat('H:i', substr($this->endTimes[$day], 0, 5))->format('H:i:s');

                $this->startTimes[$day] = $startTime;
                $this->endTimes[$day] = $endTime;
            }
        }

        $this->reservationHoursService->saveHours(new SaveReservationHoursData(
            restaurant: $this->restaurant,
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

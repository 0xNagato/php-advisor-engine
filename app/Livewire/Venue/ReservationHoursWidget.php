<?php

namespace App\Livewire\Venue;

use App\Data\Venue\SaveReservationHoursBlockData;
use App\Models\Venue;
use App\Services\ReservationHoursService;
use Carbon\Carbon;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class ReservationHoursWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithRateLimiting;

    protected static string $view = 'livewire.venue.open-hours-widget';

    protected static ?string $pollingInterval = null;

    public array $openingHours = [];

    public array $selectedDays = [];

    public array $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public Venue $venue;

    protected ReservationHoursService $reservationHoursService;

    public function boot(ReservationHoursService $reservationHoursService): void
    {
        $this->reservationHoursService = $reservationHoursService;
    }

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
        $data = $this->reservationHoursService->loadHours($this->venue);

        $this->openingHours = $data->openingHours;
        $this->selectedDays = $data->selectedDays;
    }

    public function updatedSelectedDays($value, $key): void
    {
        if ($value === true && blank($this->openingHours[$key])) {
            $this->openingHours[$key] = [
                [
                    'start_time' => Venue::DEFAULT_START_HOUR.':00:00',
                    'end_time' => Venue::DEFAULT_END_HOUR.':00:00',
                ],
            ];
        }
    }

    public function updatedOpeningHours($value, $key): void
    {
        foreach (['start_time', 'end_time'] as $timeKey) {
            if (isset($value[$timeKey])) {
                $timeSegment = substr((string) $value[$timeKey], 0, 5);
                $formattedTime = Carbon::createFromFormat('H:i', $timeSegment)->format('H:i:s');

                data_set($this->openingHours, "{$key}.{$timeKey}", $formattedTime);
            }
        }
    }

    public function addTimeBlock(string $day): void
    {
        $this->openingHours[$day][] = [
            'start_time' => Venue::DEFAULT_START_HOUR.':00:00',
            'end_time' => Venue::DEFAULT_END_HOUR.':00:00',
        ];
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->icon('heroicon-o-trash')
            ->button()
            ->color('danger')
            ->size('xs')
            ->hiddenLabel()
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                $this->removeTimeBlock($arguments['day'], $arguments['index']);
            });
    }

    public function saveHours(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->notifyRateLimitExceeded($exception);

            return;
        }

        foreach ($this->daysOfWeek as $day) {
            if ($this->selectedDays[$day]) {
                $this->validateOpeningHoursForDay($day);
            }
        }

        $this->reservationHoursService->saveHours(new SaveReservationHoursBlockData(
            venue: $this->venue,
            openingHours: $this->openingHours,
            selectedDays: $this->selectedDays,
        ));

        $this->dispatch('reservation-hours-updated');

        $this->notifySuccess();
    }

    private function removeTimeBlock(string $day, int $index): void
    {
        unset($this->openingHours[$day][$index]);

        $this->openingHours[$day] = array_values($this->openingHours[$day]);
    }

    private function notifyRateLimitExceeded(TooManyRequestsException|Exception $exception): void
    {
        Notification::make()
            ->title("Too many requests! Please wait another $exception->secondsUntilAvailable seconds.")
            ->danger()
            ->send();
    }

    private function notifySuccess(): void
    {
        Notification::make()
            ->title('Reservation hours saved successfully.')
            ->success()
            ->send();
    }

    private function validateOpeningHoursForDay(string $day): void
    {
        foreach ($this->openingHours[$day] ?? [] as $index => $block) {
            $startTime = $block['start_time'];
            $endTime = $this->normalizeMidnight($block['end_time']);

            $this->validateTimeBlock($day, $index, $startTime, $endTime);
        }
    }

    private function normalizeMidnight(string $time): string
    {
        return $time === '00:00:00' ? '24:00:00' : $time;
    }

    private function validateTimeBlock(string $day, int $index, string $startTime, string $endTime): void
    {
        $this->validate([
            "openingHours.$day.$index.start_time" => [
                'required',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) use ($startTime, $endTime) {
                    if ($startTime >= $endTime) {
                        $fail("The opening hour's start time must be before its end time.");
                    }
                },
            ],
            "openingHours.$day.$index.end_time" => [
                'required',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) use ($startTime, $endTime) {
                    if ($endTime <= $startTime) {
                        $fail("The opening hour's end time must be after its start time.");
                    }
                },
            ],
        ], [
            "openingHours.$day.$index.start_time.required" => "The start time for $day is required.",
            "openingHours.$day.$index.end_time.required" => "The end time for $day is required.",
            "openingHours.$day.$index.start_time.date_format" => 'The start time must be in the format H:i:s.',
            "openingHours.$day.$index.end_time.date_format" => 'The end time must be in the format H:i:s.',
        ]);
    }
}

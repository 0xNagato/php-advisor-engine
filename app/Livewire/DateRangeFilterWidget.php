<?php

namespace App\Livewire;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class DateRangeFilterWidget extends Widget
{
    protected static string $view = 'livewire.date-range-filter-widget';

    protected static ?string $pollingInterval = null;

    /**
     * This needs to be set to false, so it doesn't load the widget background
     */
    protected static bool $isLazy = false;

    #[Url('range')]
    public string $range = 'past_30_days';

    public string $startDate = '';

    public string $endDate = '';

    public array $ranges = [];

    protected function getUserTimezone(): string
    {
        return auth()->user()?->timezone ?? config('app.default_timezone');
    }

    public function mount(): void
    {
        $timezone = $this->getUserTimezone();
        $today = now($timezone);

        $this->ranges = [
            'today' => [
                'start' => $today->toDateString(),
                'end' => $today->toDateString(),
            ],
            'past_week' => [
                'start' => $today->copy()->subDays(6)->toDateString(),
                'end' => $today->toDateString(),
            ],
            'month' => [
                'start' => $today->copy()->startOfMonth()->toDateString(),
                'end' => $today->copy()->endOfMonth()->toDateString(),
            ],
            'past_30_days' => [
                'start' => $today->copy()->subDays(30)->toDateString(),
                'end' => $today->toDateString(),
            ],
            'year' => [
                'start' => $today->copy()->startOfYear()->toDateString(),
                'end' => $today->copy()->endOfYear()->toDateString(),
            ],
        ];
    }

    public function setDateRange(string $range): void
    {
        if (isset($this->ranges[$range])) {
            $this->range = $range;
            // Ensure start date is never after end date
            $startDate = $this->ranges[$range]['start'];
            $endDate = $this->ranges[$range]['end'];

            if (strtotime((string) $startDate) <= strtotime((string) $endDate)) {
                $this->startDate = $startDate;
                $this->endDate = $endDate;
            } else {
                // Swap if reversed
                $this->startDate = $endDate;
                $this->endDate = $startDate;
            }
        }

        $this->dispatch('dateRangeUpdated', startDate: $this->startDate, endDate: $this->endDate);
    }

    #[Computed]
    public function getStartDate(): ?Carbon
    {
        return $this->startDate
            ? Carbon::parse($this->startDate)->setTimezone($this->getUserTimezone())
            : null;
    }

    #[Computed]
    public function getEndDate(): ?Carbon
    {
        return $this->endDate
            ? Carbon::parse($this->endDate)->setTimezone($this->getUserTimezone())
            : null;
    }
}

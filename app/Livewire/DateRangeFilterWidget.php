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
     * This needs to be set to false so it doesn't load the widget background
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

        $this->ranges = [
            'today' => [
                'start' => today()->toDateString(),
                'end' => now($timezone)->endOfDay()->toDateString(),
            ],
            'past_week' => [
                'start' => now($timezone)->subDays(6)->toDateString(),
                'end' => now($timezone)->toDateString(),
            ],
            'month' => [
                'start' => now($timezone)->startOfMonth()->toDateString(),
                'end' => now($timezone)->endOfMonth()->toDateString(),
            ],
            'past_30_days' => [
                'start' => now($timezone)->subDays(30)->toDateString(),
                'end' => now($timezone)->toDateString(),
            ],
            'year' => [
                'start' => now($timezone)->startOfYear()->toDateString(),
                'end' => now($timezone)->endOfYear()->toDateString(),
            ],
        ];
    }

    public function setDateRange(string $range): void
    {
        $this->range = $range;
        if (isset($this->ranges[$range])) {
            $this->range = $range;
            $this->startDate = $this->ranges[$range]['start'];
            $this->endDate = $this->ranges[$range]['end'];
        }

        $this->dispatch('dateRangeUpdated', startDate: $this->startDate, endDate: $this->endDate);
    }

    #[Computed]
    public function getStartDate(): ?Carbon
    {
        return $this->startDate ? Carbon::parse($this->startDate)->setTimezone($this->getUserTimezone()) : null;
    }

    #[Computed]
    public function getEndDate(): ?Carbon
    {
        return $this->endDate ? Carbon::parse($this->endDate)->setTimezone($this->getUserTimezone()) : null;
    }
}

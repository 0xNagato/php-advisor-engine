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

    public function mount(): void
    {
        $this->ranges = [
            'past_30_days' => [
                'start' => now()->subDays(30)->toDateString(),
                'end' => now()->toDateString(),
            ],
            'past_week' => [
                'start' => now()->subDays(6)->toDateString(),
                'end' => now()->toDateString(),
            ],
            'month' => [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->endOfMonth()->toDateString(),
            ],
            'quarter' => [
                'start' => now()->startOfQuarter()->toDateString(),
                'end' => now()->endOfQuarter()->toDateString(),
            ],
            'year' => [
                'start' => now()->startOfYear()->toDateString(),
                'end' => now()->endOfYear()->toDateString(),
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
        return $this->startDate ? Carbon::parse($this->startDate) : null;
    }

    #[Computed]
    public function getEndDate(): ?Carbon
    {
        return $this->endDate ? Carbon::parse($this->endDate) : null;
    }
}

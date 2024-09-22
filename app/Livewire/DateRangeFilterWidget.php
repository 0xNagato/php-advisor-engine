<?php

namespace App\Livewire;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

class DateRangeFilterWidget extends Widget
{
    protected static string $view = 'livewire.date-range-filter-widget';

    public string $range = 'past_30_days';

    public string $startDate = '';

    public string $endDate = '';

    public function setDateRange(string $range): void
    {
        $this->range = $range;

        switch ($range) {
            case 'past_30_days':
                $this->startDate = now()->subDays(30)->toDateString();
                $this->endDate = now()->toDateString();
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->toDateString();
                $this->endDate = now()->endOfWeek()->toDateString();
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->toDateString();
                $this->endDate = now()->endOfMonth()->toDateString();
                break;
            case 'quarter':
                $this->startDate = now()->startOfQuarter()->toDateString();
                $this->endDate = now()->endOfQuarter()->toDateString();
                break;
            case 'year':
                $this->startDate = now()->startOfYear()->toDateString();
                $this->endDate = now()->endOfYear()->toDateString();
                break;
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

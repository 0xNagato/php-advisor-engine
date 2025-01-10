<?php

namespace App\Livewire\Form;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

class DateRangeInputWidget extends Widget
{
    protected static string $view = 'livewire.form.date-range-input-widget';

    protected static ?string $pollingInterval = null;

    /**
     * This needs to be set to false so it doesn't load the widget background
     */
    protected static bool $isLazy = false;

    public $startDate;

    public $endDate;

    public function mount($startDate, $endDate): void
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function updateDateRange(): void
    {
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

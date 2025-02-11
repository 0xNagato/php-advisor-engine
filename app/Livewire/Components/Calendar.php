<?php

namespace App\Livewire\Components;

use Carbon\Carbon;
use Livewire\Component;

class Calendar extends Component
{
    public ?string $selectedDate = null;

    public Carbon $currentMonth;

    public function mount(): void
    {
        $this->currentMonth = Carbon::now()->startOfMonth();
    }

    public function previousMonth(): void
    {
        $this->currentMonth->subMonth()->startOfMonth();
    }

    public function nextMonth(): void
    {
        $this->currentMonth->addMonth()->startOfMonth();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->dispatch('calendar-date-selected', date: $date);
    }

    protected function getWeeks(): array
    {
        $start = $this->currentMonth->copy()->startOfMonth();
        // Get to the first Sunday before or on the start of the month
        if ($start->dayOfWeek !== Carbon::SUNDAY) {
            $start->previous(Carbon::SUNDAY);
        }

        $end = $this->currentMonth->copy()->endOfMonth();
        // Get to the last Saturday after or on the end of the month
        if ($end->dayOfWeek !== Carbon::SATURDAY) {
            $end->next(Carbon::SATURDAY);
        }

        $days = [];
        $currentDay = $start->copy();

        while ($currentDay->lte($end)) {
            $days[] = $currentDay->copy();
            $currentDay->addDay();
        }

        return array_chunk($days, 7);
    }

    public function render()
    {
        return view('livewire.components.calendar', [
            'weeks' => $this->getWeeks(),
        ]);
    }
}

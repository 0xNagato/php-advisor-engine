<?php

namespace App\Livewire\Components;

use Carbon\Carbon;
use Livewire\Component;

class Calendar extends Component
{
    public ?string $selectedDate = null;

    public ?string $todayDate = null;

    public string $currentMonth;

    public ?string $timezone;

    public array $datesWithOverrides = [];

    public function mount(
        ?string $selectedDate = null,
        ?string $todayDate = null,
        ?string $timezone = null,
        array $datesWithOverrides = []
    ): void {
        $this->timezone = $timezone ?? config('app.timezone');
        $this->selectedDate = $selectedDate;
        $this->todayDate = $todayDate;
        $this->datesWithOverrides = $datesWithOverrides;

        // Set current month based on selected date or today
        if ($selectedDate) {
            $this->currentMonth = Carbon::parse($selectedDate, $this->timezone)->format('Y-m');
        } else {
            $this->currentMonth = now($this->timezone)->format('Y-m');
        }
    }

    public function previousMonth(): void
    {
        $current = Carbon::createFromFormat('Y-m', $this->currentMonth, $this->timezone);
        $this->currentMonth = $current->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $current = Carbon::createFromFormat('Y-m', $this->currentMonth, $this->timezone);
        $this->currentMonth = $current->addMonth()->format('Y-m');
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->currentMonth = Carbon::parse($date, $this->timezone)->format('Y-m');
        $this->dispatch('calendar-date-selected', date: $date);
    }

    protected function getWeeks(): array
    {
        $current = Carbon::createFromFormat('Y-m', $this->currentMonth, $this->timezone);
        $start = $current->copy()->startOfMonth();

        if ($start->dayOfWeek !== Carbon::SUNDAY) {
            $start->previous(Carbon::SUNDAY);
        }

        $end = $current->copy()->endOfMonth();
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
            'currentMonthCarbon' => Carbon::createFromFormat('Y-m', $this->currentMonth, $this->timezone),
        ]);
    }
}

<?php

namespace App\Livewire\Components;

use Carbon\Carbon;
use Livewire\Component;

class Calendar extends Component
{
    public ?string $selectedDate = null;

    public ?string $todayDate = null;

    public string $currentMonth;

    public ?string $timezone = null;

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
        // Extract year and month as integers
        [$year, $month] = explode('-', $this->currentMonth);
        $year = (int) $year;
        $month = (int) $month;

        // Decrement month and handle year change
        $month--;
        if ($month === 0) {
            $month = 12;
            $year--;
        }

        // Format back as Y-m
        $this->currentMonth = sprintf('%04d-%02d', $year, $month);
    }

    public function nextMonth(): void
    {
        // Extract year and month as integers
        [$year, $month] = explode('-', $this->currentMonth);
        $year = (int) $year;
        $month = (int) $month;

        // Increment month and handle year change
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }

        // Format back as Y-m
        $this->currentMonth = sprintf('%04d-%02d', $year, $month);
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->currentMonth = Carbon::parse($date, $this->timezone)->format('Y-m');
        $this->dispatch('calendar-date-selected', date: $date);
    }

    protected function getWeeks(): array
    {
        // Create first day of the month to avoid any day overflow issues
        $current = Carbon::parse($this->currentMonth.'-01', $this->timezone);
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
        // Create first day of the month to avoid any day overflow issues
        $currentMonthCarbon = Carbon::parse($this->currentMonth.'-01', $this->timezone);

        return view('livewire.components.calendar', [
            'weeks' => $this->getWeeks(),
            'currentMonthCarbon' => $currentMonthCarbon,
        ]);
    }
}

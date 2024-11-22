<?php

namespace App\Livewire\Venue;

use App\Models\ScheduleTemplate;
use App\Models\Venue;
use Carbon\Carbon;
use Exception;
use Filament\Notifications\Notification;
use Ijpatricio\Mingle\Concerns\InteractsWithMingles;
use Ijpatricio\Mingle\Contracts\HasMingles;
use Livewire\Component;

class WeeklyPrimeSchedule extends Component implements HasMingles
{
    use InteractsWithMingles;

    protected Venue $venue;

    protected array $weeklySchedule = [];

    protected array $selectedTimeSlots = [];

    protected array $operatingHours;

    public function boot(): void
    {
        $this->venue = auth()->user()->venue;
        $this->operatingHours = $this->getOperatingHours();
        $this->generateWeeklySchedule();
        $this->initializeSelectedTimeSlots();
    }

    public function component(): string
    {
        return 'resources/js/Venue/WeeklyPrimeSchedule/index.js';
    }

    public function mingleData(): array
    {
        return [
            'earliestStartTime' => $this->operatingHours['earliest_start_time']->format('H:i'),
            'latestEndTime' => $this->operatingHours['latest_end_time']->format('H:i'),
            'weeklySchedule' => $this->weeklySchedule,
            'selectedTimeSlots' => $this->selectedTimeSlots,
            'openDays' => $this->venue->open_days,
        ];
    }

    protected function getOperatingHours(): array
    {
        $hours = $this->venue->getOperatingHours();

        return [
            'earliest_start_time' => Carbon::parse($hours['earliest_start_time']),
            'latest_end_time' => Carbon::parse($hours['latest_end_time']),
        ];
    }

    protected function generateWeeklySchedule(): void
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOfWeek as $day) {
            $this->weeklySchedule[$day] = $this->venue->open_days[$day] === 'open'
                ? $this->generateTimeSlotsForDay($day)
                : 'closed';
        }
    }

    protected function generateTimeSlotsForDay(string $day): array
    {
        $slots = [];
        $currentTime = $this->operatingHours['earliest_start_time']->copy();
        $endTime = $this->operatingHours['latest_end_time'];

        while ($currentTime->lessThan($endTime)) {
            $timeSlot = $currentTime->format('H:i:s');
            $scheduleTemplate = $this->venue->scheduleTemplates()
                ->where('day_of_week', $day)
                ->where('start_time', $timeSlot)
                ->first();

            $slots[] = [
                'start' => $timeSlot,
                'end' => $currentTime->copy()->addMinutes(30)->format('H:i:s'),
                'is_prime' => $scheduleTemplate->prime_time ?? false,
                'is_available' => $scheduleTemplate->is_available ?? false,
                'schedule_template_id' => $scheduleTemplate->id ?? null,
            ];

            $currentTime->addMinutes(30);
        }

        return $slots;
    }

    protected function initializeSelectedTimeSlots(): void
    {
        foreach ($this->weeklySchedule as $day => $slots) {
            if (is_array($slots)) {
                $this->selectedTimeSlots[$day] = collect($slots)->pluck('is_prime')->toArray();
            }
        }
    }

    public function save(array $selectedTimeSlots): array
    {
        try {
            $this->selectedTimeSlots = $selectedTimeSlots;

            foreach ($this->weeklySchedule as $day => $slots) {
                if (is_array($slots)) {
                    foreach ($slots as $index => $slot) {
                        if ($slot['schedule_template_id']) {
                            ScheduleTemplate::query()
                                ->where('venue_id', $this->venue->id)
                                ->where('day_of_week', $day)
                                ->where('start_time', $slot['start'])
                                ->update(['prime_time' => $this->selectedTimeSlots[$day][$index]]);
                        }
                    }
                }
            }

            Notification::make()
                ->title('Weekly prime schedule saved successfully.')
                ->success()
                ->send();

            $this->dispatch('weekly-schedule-updated');

            return ['success' => true, 'message' => 'Weekly prime schedule saved successfully.'];
        } catch (Exception $e) {
            report($e);
            Notification::make()
                ->title('Error saving weekly prime schedule.')
                ->danger()
                ->send();

            return ['success' => false, 'message' => 'Error saving weekly prime schedule.'];
        }
    }
}

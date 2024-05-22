<?php

namespace App\Filament\Pages\Restaurant;

use App\Models\Restaurant;
use App\Models\RestaurantTimeSlot;
use App\Models\ScheduleTemplate;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class PrimeCalendar extends Page
{
    public const int DAYS_TO_DISPLAY = 30;

    protected static ?string $navigationIcon = 'polaris-calendar-time-icon';

    protected static ?int $navigationSort = 21;

    protected static string $view = 'filament.pages.restaurant.prime-calendar';

    public Restaurant $restaurant;

    public Collection $upcomingDates;

    public array $timeSlots = [];

    public array $selectedTimeSlots = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    public function mount(): void
    {
        $this->restaurant = auth()->user()->restaurant;
        $this->upcomingDates = collect(range(0, self::DAYS_TO_DISPLAY - 1))
            ->map(fn ($days) => Carbon::now()->addDays($days));
        $this->generateTimeSlots();
        $this->initializeSelectedTimeSlots();
    }

    private function generateTimeSlots(): void
    {
        $operatingHours = $this->restaurant->getOperatingHours();
        $earliestStartTime = Carbon::parse($operatingHours['earliest_start_time']);
        $latestEndTime = Carbon::parse($operatingHours['latest_end_time']);

        // Fetch all schedule templates for the restaurant
        $scheduleTemplates = ScheduleTemplate::where('restaurant_id', $this->restaurant->id)
            ->get()
            ->groupBy('day_of_week');

        // Fetch all overrides in one go and group them by schedule_template_id and booking_date
        $overrides = RestaurantTimeSlot::whereIn('booking_date', $this->upcomingDates->map(fn ($date) => $date->format('Y-m-d'))->toArray())
            ->get()
            ->groupBy(['schedule_template_id', 'booking_date']);

        foreach ($this->upcomingDates as $date) {
            $slots = [];
            $currentTime = $date->copy()->setTimeFromTimeString($earliestStartTime->format('H:i:s'));
            $endTime = $date->copy()->setTimeFromTimeString($latestEndTime->format('H:i:s'));
            $dayOfWeek = strtolower($date->format('l'));

            while ($currentTime->lessThan($endTime)) {
                $timeSlotStart = $currentTime->copy()->format('H:i:s');
                $timeSlotEnd = $currentTime->copy()->addMinutes(30)->format('H:i:s');

                // Find the correct ScheduleTemplate
                $scheduleTemplate = $scheduleTemplates[$dayOfWeek]->firstWhere('start_time', $timeSlotStart);

                // Check if there's an override for this schedule template and booking date
                $override = $scheduleTemplate ? $overrides[$scheduleTemplate->id][$date->format('Y-m-d')][0] ?? null : null;

                $slots[] = [
                    'start' => $timeSlotStart,
                    'end' => $timeSlotEnd,
                    'is_checked' => ! $override || $override->prime_time,
                    'override_id' => $override?->id,
                    'schedule_template_id' => $scheduleTemplate?->id,
                ];

                $currentTime->addMinutes(30);
            }

            $this->timeSlots[$date->format('Y-m-d')] = $slots;
        }
    }

    private function initializeSelectedTimeSlots(): void
    {
        foreach ($this->upcomingDates as $date) {
            $this->selectedTimeSlots[$date->format('Y-m-d')] = [];
            foreach ($this->timeSlots[$date->format('Y-m-d')] as $index => $slot) {
                $this->selectedTimeSlots[$date->format('Y-m-d')][$index] = $slot['is_checked'];
            }
        }
    }

    public function save(): void
    {
        foreach ($this->selectedTimeSlots as $date => $slots) {
            foreach ($slots as $index => $isChecked) {
                $slot = $this->timeSlots[$date][$index];

                if ($slot['override_id']) {
                    $timeSlot = RestaurantTimeSlot::find($slot['override_id']);
                    $timeSlot->update(['prime_time' => $isChecked]);
                } elseif (! $isChecked) {
                    RestaurantTimeSlot::create([
                        'schedule_template_id' => $slot['schedule_template_id'],
                        'booking_date' => $date,
                        'start_time' => $slot['start'],
                        'prime_time' => $isChecked,
                    ]);
                }
            }
        }

        Notification::make()
            ->title('Prime calendar saved successfully.')
            ->success()
            ->send();
    }
}

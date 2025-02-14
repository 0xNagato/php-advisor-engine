<?php

namespace App\Livewire\Venue;

use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VenueTimeSlot;
use Carbon\Carbon;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ScheduleManager extends Component
{
    public Venue $venue;

    public string $activeView = 'template';

    public string $selectedDay = 'monday';

    public ?string $selectedDate = null;

    public ?string $todayDate = null;

    public array $schedules = [];

    public array $timeSlots = [];

    public array $calendarSchedules = [];

    protected array $operatingHours;

    public array $editingSlot = [
        'day' => '',
        'time' => '',
        'size' => '',
        'is_available' => false,
        'is_prime' => false,
        'price_per_head' => 0,
        'available_tables' => 0,
        'minimum_spend_per_guest' => 0,
        'template_id' => null,
    ];

    protected $listeners = [
        'calendar-date-selected' => 'handleDateSelection',
    ];

    public function mount(): void
    {
        $this->operatingHours = $this->getOperatingHours();
        $this->generateTimeSlots();
        $this->initializeSchedules();

        // Initialize calendarSchedules array
        $this->calendarSchedules = [];

        // Set today's date using the venue's timezone
        $timezone = $this->venue->timezone ?? config('app.timezone');
        $today = now($timezone);  // Create Carbon instance directly in venue timezone

        $this->todayDate = $today->format('Y-m-d');
        $this->selectedDate = $this->todayDate;
    }

    public function updatedActiveView(string $value): void
    {
        if ($value === 'calendar') {
            $timezone = $this->venue->timezone ?? config('app.timezone');

            if (! $this->selectedDate) {
                $today = now($timezone);

                // If venue is closed today, find the next open day
                $dayOfWeek = strtolower($today->format('l'));
                if ($this->venue->open_days[$dayOfWeek] === 'closed') {
                    // Check next 7 days for an open day
                    for ($i = 1; $i <= 7; $i++) {
                        $nextDay = $today->copy()->addDays($i);
                        $nextDayOfWeek = strtolower($nextDay->format('l'));
                        if ($this->venue->open_days[$nextDayOfWeek] === 'open') {
                            $today = $nextDay;
                            break;
                        }
                    }
                }

                $this->selectedDate = $today->format('Y-m-d');
            }

            // Load the schedules for the selected date
            $this->handleDateSelection($this->selectedDate);
        } else {
            // Clear calendar data when switching to template view
            $this->calendarSchedules = [];
        }
    }

    protected function getOperatingHours(): array
    {
        $hours = $this->venue->getOperatingHours();

        return [
            'earliest_start_time' => Carbon::parse($hours['earliest_start_time']),
            'latest_end_time' => Carbon::parse($hours['latest_end_time']),
        ];
    }

    protected function generateTimeSlots(): void
    {
        $this->timeSlots = [];
        $currentTime = $this->operatingHours['earliest_start_time']->copy();
        $endTime = $this->operatingHours['latest_end_time'];

        while ($currentTime->lessThan($endTime)) {
            $this->timeSlots[] = [
                'time' => $currentTime->format('H:i:s'),
                'formatted_time' => $currentTime->format('g:i A'),
            ];
            $currentTime->addMinutes(30);
        }
    }

    protected function initializeSchedules(): void
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $this->schedules = []; // Ensure we start with an empty array

        foreach ($daysOfWeek as $day) {
            if ($this->venue->open_days[$day] === 'closed') {
                $this->schedules[$day] = 'closed';

                continue;
            }

            // Initialize the day as an array
            $this->schedules[$day] = [];

            // Get all templates for this day
            $templates = $this->venue->scheduleTemplates()
                ->where('day_of_week', $day)
                ->get()
                ->groupBy(['start_time', 'party_size']);

            // Initialize schedule structure for each time slot
            foreach ($this->timeSlots as $slot) {
                $this->schedules[$day][$slot['time']] = [];

                foreach ($this->venue->party_sizes as $size => $label) {
                    if ($size === 'Special Request') {
                        continue;
                    }

                    $template = $templates[$slot['time']][$size] ?? null;
                    $template = $template[0] ?? null;

                    $this->schedules[$day][$slot['time']][$size] = [
                        'is_available' => $template?->is_available ?? false,
                        'is_prime' => $template?->prime_time ?? false,
                        'available_tables' => $template?->available_tables ?? 0,
                        'template_id' => $template?->id,
                        'minimum_spend_per_guest' => $template?->minimum_spend_per_guest ?? 0,
                        'price_per_head' => $template?->price_per_head ?? $this->venue->non_prime_fee_per_head,
                    ];
                }
            }
        }
    }

    public function saveTemplate(): void
    {
        try {
            // Get only the time slots we're actually modifying
            $updates = [];
            if ($this->editingSlot['size'] === '*') {
                // Bulk editing all party sizes for this time
                foreach ($this->venue->party_sizes as $size => $label) {
                    if ($size === 'Special Request') {
                        continue;
                    }
                    $updates[] = [
                        'day_of_week' => $this->selectedDay,
                        'start_time' => $this->editingSlot['time'],
                        'party_size' => $size,
                        'data' => $this->schedules[$this->selectedDay][$this->editingSlot['time']][$size],
                    ];
                }
            } else {
                // Single slot edit
                $updates[] = [
                    'day_of_week' => $this->selectedDay,
                    'start_time' => $this->editingSlot['time'],
                    'party_size' => $this->editingSlot['size'],
                    'data' => $this->schedules[$this->selectedDay][$this->editingSlot['time']][$this->editingSlot['size']],
                ];
            }

            // Single bulk update query for all affected slots
            $this->venue->scheduleTemplates()
                ->where('day_of_week', $this->selectedDay)
                ->where('start_time', $this->editingSlot['time'])
                ->when($this->editingSlot['size'] !== '*', function (Builder $query) {
                    $query->where('party_size', $this->editingSlot['size']);
                })
                ->update([
                    'is_available' => $updates[0]['data']['is_available'],
                    'prime_time' => $updates[0]['data']['is_prime'],
                    'available_tables' => $updates[0]['data']['available_tables'],
                    'price_per_head' => $updates[0]['data']['price_per_head'] ?? $this->venue->non_prime_fee_per_head,
                    'minimum_spend_per_guest' => $updates[0]['data']['minimum_spend_per_guest'] ?? 0,
                    'updated_at' => now(),
                ]);

        } catch (Exception $e) {
            Log::error('Error saving schedule template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to be caught by saveEditingSlot
        }
    }

    public function openEditModal(string $date, string $time, string $size): void
    {
        if ($this->activeView === 'template') {
            // Handle template editing
            $schedule = $this->schedules[$this->selectedDay][$time][$size] ?? null;
            $dayOfWeek = $this->selectedDay;
        } else {
            // Handle calendar override editing
            $schedule = $this->calendarSchedules[$time][$size] ?? null;
            $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));
        }

        if (! $schedule) {
            return;
        }

        $this->editingSlot = [
            'day' => $dayOfWeek,
            'date' => $date,
            'time' => $time,
            'size' => $size,
            'is_available' => $schedule['is_available'],
            'is_prime' => $schedule['is_prime'],
            'available_tables' => $schedule['available_tables'],
            'minimum_spend_per_guest' => $schedule['minimum_spend_per_guest'],
            'price_per_head' => $schedule['price_per_head'],
            'template_id' => $schedule['template_id'] ?? null,
        ];

        $this->dispatch('open-modal', id: 'edit-slot');
    }

    public function closeEditModal(): void
    {
        $this->dispatch('close-modal', id: 'edit-slot');
        $this->editingSlot = [
            'day' => '',
            'time' => '',
            'size' => '',
            'is_available' => false,
            'is_prime' => false,
            'price_per_head' => 0,
            'available_tables' => 0,
            'minimum_spend_per_guest' => 0,
            'template_id' => null,
        ];
    }

    public function saveEditingSlot(): void
    {
        try {
            if ($this->activeView === 'template') {
                // Template editing
                if ($this->editingSlot['size'] === '*') {
                    // Bulk edit for all party sizes
                    foreach ($this->venue->party_sizes as $size => $label) {
                        if ($size === 'Special Request') {
                            continue;
                        }

                        $this->schedules[$this->selectedDay][$this->editingSlot['time']][$size] = [
                            'is_available' => $this->editingSlot['is_available'],
                            'is_prime' => $this->editingSlot['is_prime'],
                            'available_tables' => $this->editingSlot['available_tables'],
                            'minimum_spend_per_guest' => $this->editingSlot['minimum_spend_per_guest'],
                            'price_per_head' => $this->editingSlot['price_per_head'],
                            'template_id' => $this->editingSlot['template_id'],
                        ];
                    }
                } else {
                    // Single party size edit
                    $this->schedules[$this->selectedDay][$this->editingSlot['time']][$this->editingSlot['size']] = [
                        'is_available' => $this->editingSlot['is_available'],
                        'is_prime' => $this->editingSlot['is_prime'],
                        'available_tables' => $this->editingSlot['available_tables'],
                        'minimum_spend_per_guest' => $this->editingSlot['minimum_spend_per_guest'],
                        'price_per_head' => $this->editingSlot['price_per_head'],
                        'template_id' => $this->editingSlot['template_id'],
                    ];
                }

                $this->saveTemplate();

                Notification::make()
                    ->title('Schedule template saved successfully')
                    ->success()
                    ->send();
            } else {
                if ($this->editingSlot['size'] === '*') {
                    // Bulk edit for all party sizes
                    $templates = $this->venue->scheduleTemplates()
                        ->where('day_of_week', $this->editingSlot['day'])
                        ->where('start_time', $this->editingSlot['time'])
                        ->get();

                    foreach ($templates as $template) {
                        $this->saveOverride($template);
                    }
                } else {
                    $template = $this->venue->scheduleTemplates()
                        ->where('day_of_week', $this->editingSlot['day'])
                        ->where('start_time', $this->editingSlot['time'])
                        ->where('party_size', $this->editingSlot['size'])
                        ->first();

                    if ($template) {
                        $this->saveOverride($template);
                    }
                }

                // Refresh the calendar schedules
                $this->handleDateSelection($this->editingSlot['date']);

                Notification::make()
                    ->title('Schedule saved successfully')
                    ->success()
                    ->send();
            }

            $this->closeEditModal();

        } catch (Exception $e) {
            Log::error('Error saving schedule', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'editingSlot' => $this->editingSlot,
                'activeView' => $this->activeView,
            ]);

            Notification::make()
                ->title('Error updating schedule')
                ->body('Please try again or contact support if the problem persists.')
                ->danger()
                ->send();
        }
    }

    protected function saveOverride(ScheduleTemplate $template): void
    {
        try {
            $bookingDate = Carbon::parse($this->editingSlot['date'])->format('Y-m-d');

            // First find if an override exists
            $override = VenueTimeSlot::query()->where('schedule_template_id', $template->id)
                ->where('booking_date', $bookingDate)
                ->first();

            if ($override) {
                $override->update([
                    'is_available' => $this->editingSlot['is_available'],
                    'prime_time' => $this->editingSlot['is_prime'],
                    'available_tables' => $this->editingSlot['available_tables'],
                    'minimum_spend_per_guest' => $this->editingSlot['minimum_spend_per_guest'],
                    'price_per_head' => $this->editingSlot['price_per_head'],
                ]);
            } else {
                VenueTimeSlot::query()->create([
                    'schedule_template_id' => $template->id,
                    'booking_date' => $bookingDate,
                    'is_available' => $this->editingSlot['is_available'],
                    'prime_time' => $this->editingSlot['is_prime'],
                    'available_tables' => $this->editingSlot['available_tables'],
                    'minimum_spend_per_guest' => $this->editingSlot['minimum_spend_per_guest'],
                    'price_per_head' => $this->editingSlot['price_per_head'],
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error in saveOverride', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'template_id' => $template->id,
                'booking_date' => $bookingDate ?? null,
            ]);
            throw $e;
        }
    }

    public function openBulkEditModal(string $day, string $time): void
    {
        // Use the first party size's settings as default
        $firstSize = array_key_first(array_filter($this->venue->party_sizes, fn ($size) => $size !== 'Special Request'));
        $schedule = $this->calendarSchedules[$time][$firstSize] ?? null;

        if (! $schedule) {
            return;
        }

        $this->editingSlot = [
            'day' => $day,
            'date' => $this->selectedDate,
            'time' => $time,
            'size' => '*',  // Special marker for bulk edit
            'is_available' => $schedule['is_available'],
            'is_prime' => $schedule['is_prime'],
            'price_per_head' => $schedule['price_per_head'],
            'available_tables' => $schedule['available_tables'],
            'minimum_spend_per_guest' => $schedule['minimum_spend_per_guest'],
        ];

        $this->dispatch('open-modal', id: 'edit-slot');
    }

    public function openBulkTemplateEditModal(string $time): void
    {
        // Use the first party size's settings as default
        $firstSize = array_key_first(array_filter($this->venue->party_sizes, fn ($size) => $size !== 'Special Request'));
        $schedule = $this->schedules[$this->selectedDay][$time][$firstSize] ?? null;

        if (! $schedule) {
            return;
        }

        $this->editingSlot = [
            'day' => $this->selectedDay,
            'date' => null,
            'time' => $time,
            'size' => '*',
            'is_available' => $schedule['is_available'],
            'is_prime' => $schedule['is_prime'],
            'available_tables' => $schedule['available_tables'],
            'minimum_spend_per_guest' => $schedule['minimum_spend_per_guest'],
            'price_per_head' => $schedule['price_per_head'],
            'template_id' => $schedule['template_id'],
        ];

        $this->dispatch('open-modal', id: 'edit-slot');
    }

    protected function getFormattedTime(): string
    {
        if (blank($this->editingSlot['time'])) {
            return '';
        }

        try {
            return Carbon::parse($this->editingSlot['time'])->format('g:i A');
        } catch (Exception) {
            return $this->editingSlot['time'];
        }
    }

    public function handleDateSelection(string $date): void
    {
        $this->selectedDate = $date;
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));

        // Get base template schedules for this day
        $templates = $this->venue->scheduleTemplates()
            ->where('day_of_week', $dayOfWeek)
            ->get();

        // Get any overrides for this specific date
        $overrides = VenueTimeSlot::query()
            ->whereIn('schedule_template_id', $templates->pluck('id'))
            ->where('booking_date', $date)
            ->get();

        // Group templates and overrides for easier lookup
        $groupedTemplates = $templates->groupBy(fn ($template) => $template->start_time.'|'.$template->party_size);

        $groupedOverrides = $overrides->groupBy('schedule_template_id');

        // Initialize calendar schedules
        $this->calendarSchedules = [];

        foreach ($this->timeSlots as $slot) {
            $this->calendarSchedules[$slot['time']] = [];

            foreach ($this->venue->party_sizes as $size => $label) {
                if ($size === 'Special Request') {
                    continue;
                }

                // Find template for this time and party size
                $template = $groupedTemplates->get($slot['time'].'|'.$size)?->first();
                $override = null;

                if ($template) {
                    $override = $groupedOverrides->get($template->id)?->first();
                }

                $this->calendarSchedules[$slot['time']][$size] = [
                    'is_available' => $override?->is_available ?? $template?->is_available ?? false,
                    'is_prime' => $override?->prime_time ?? $template?->prime_time ?? false,
                    'available_tables' => $override?->available_tables ?? $template?->available_tables ?? 0,
                    'template_id' => $template?->id,
                    'minimum_spend_per_guest' => $override?->minimum_spend_per_guest ?? $template?->minimum_spend_per_guest ?? 0,
                    'has_override' => $override !== null,
                    'price_per_head' => $override?->price_per_head ?? $template?->price_per_head ?? $this->venue->non_prime_fee_per_head,
                ];
            }
        }
    }

    public function closeDay(): void
    {
        try {
            $dayOfWeek = strtolower(Carbon::parse($this->selectedDate)->format('l'));

            // Get all templates for this day
            $templates = $this->venue->scheduleTemplates()
                ->where('day_of_week', $dayOfWeek)
                ->get();

            // Create overrides for each template setting is_available to false
            foreach ($templates as $template) {
                VenueTimeSlot::query()->updateOrCreate([
                    'schedule_template_id' => $template->id,
                    'booking_date' => $this->selectedDate,
                ], [
                    'is_available' => false,
                    'prime_time' => false,
                ]);
            }

            // Refresh the calendar schedules
            $this->handleDateSelection($this->selectedDate);

            Notification::make()
                ->title('All time slots have been closed for this day')
                ->success()
                ->send();

        } catch (Exception $e) {
            Log::error('Error closing day', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error closing day')
                ->body('Please try again or contact support if the problem persists.')
                ->danger()
                ->send();
        }
    }

    public function makeDayPrime(): void
    {
        try {
            $dayOfWeek = strtolower(Carbon::parse($this->selectedDate)->format('l'));

            // Get all templates for this day
            $templates = $this->venue->scheduleTemplates()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_available', true)
                ->get();

            // Create overrides for each template setting prime_time to true
            foreach ($templates as $template) {
                VenueTimeSlot::query()->updateOrCreate([
                    'schedule_template_id' => $template->id,
                    'booking_date' => $this->selectedDate,
                ], [
                    'is_available' => true,
                    'prime_time' => true,
                    'available_tables' => $template->available_tables,
                    'minimum_spend_per_guest' => $template->minimum_spend_per_guest ?? 0,
                ]);
            }

            // Refresh the calendar schedules
            $this->handleDateSelection($this->selectedDate);

            Notification::make()
                ->title('All available time slots have been set to prime time')
                ->success()
                ->send();

        } catch (Exception $e) {
            Log::error('Error setting prime time', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error setting prime time')
                ->body('Please try again or contact support if the problem persists.')
                ->danger()
                ->send();
        }
    }

    public function markDaySoldOut(): void
    {
        try {
            $dayOfWeek = strtolower(Carbon::parse($this->selectedDate)->format('l'));

            // Get all templates for this day
            $templates = $this->venue->scheduleTemplates()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_available', true)
                ->get();

            // Create overrides for each template setting available_tables to 0
            foreach ($templates as $template) {
                VenueTimeSlot::query()->updateOrCreate([
                    'schedule_template_id' => $template->id,
                    'booking_date' => $this->selectedDate,
                ], [
                    'is_available' => true,
                    'prime_time' => $template->prime_time,
                    'available_tables' => 0,
                    'minimum_spend_per_guest' => $template->minimum_spend_per_guest ?? 0,
                    'price_per_head' => $template->price_per_head,
                ]);
            }

            // Refresh the calendar schedules
            $this->handleDateSelection($this->selectedDate);

            Notification::make()
                ->title('All time slots have been marked as sold out')
                ->success()
                ->send();

        } catch (Exception $e) {
            Log::error('Error marking day as sold out', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error marking day as sold out')
                ->body('Please try again or contact support if the problem persists.')
                ->danger()
                ->send();
        }
    }

    protected function getHolidayInfo(string $date): ?array
    {
        $holidays = [
            '01-01' => ['emoji' => '🎊', 'name' => 'New Year\'s Day'],
            '01-06' => ['emoji' => '👑', 'name' => 'Epiphany'],
            '01-15' => ['emoji' => '✊🏾', 'name' => 'MLK Jr. Day'],
            '02-02' => ['emoji' => '🕯️', 'name' => 'Candlemas'],
            '02-14' => ['emoji' => '💝', 'name' => 'Valentine\'s Day'],
            '02-21' => ['emoji' => '🎭', 'name' => 'Mardi Gras'],
            '03-17' => ['emoji' => '☘️', 'name' => 'St. Patrick\'s Day'],
            '04-20' => ['emoji' => '🐰', 'name' => 'Easter Sunday'], // 2025 date
            '04-21' => ['emoji' => '🌸', 'name' => 'Easter Monday'], // 2025 date
            '05-01' => ['emoji' => '🌺', 'name' => 'May Day'],
            '05-05' => ['emoji' => '🌮', 'name' => 'Cinco de Mayo'],
            '05-26' => ['emoji' => '🎖️', 'name' => 'Memorial Day'],
            '06-19' => ['emoji' => '✊🏾', 'name' => 'Juneteenth'],
            '06-24' => ['emoji' => '🔥', 'name' => 'St. Jean Baptiste Day'],
            '07-01' => ['emoji' => '🍁', 'name' => 'Canada Day'],
            '07-04' => ['emoji' => '🎆', 'name' => 'Independence Day'],
            '07-14' => ['emoji' => '🇫🇷', 'name' => 'Bastille Day'],
            '08-15' => ['emoji' => '👼', 'name' => 'Assumption Day'],
            '09-01' => ['emoji' => '👷', 'name' => 'Labor Day'],
            '10-12' => ['emoji' => '🎉', 'name' => 'Spanish National Day'],
            '10-31' => ['emoji' => '🎃', 'name' => 'Halloween'],
            '11-01' => ['emoji' => '🕊️', 'name' => 'All Saints\' Day'],
            '11-11' => ['emoji' => '🎖️', 'name' => 'Veterans/Remembrance Day'],
            '11-28' => ['emoji' => '🦃', 'name' => 'Thanksgiving'],
            '12-06' => ['emoji' => '🎅', 'name' => 'St. Nicholas Day'],
            '12-24' => ['emoji' => '🎄', 'name' => 'Christmas Eve'],
            '12-25' => ['emoji' => '🎄', 'name' => 'Christmas Day'],
            '12-26' => ['emoji' => '🎁', 'name' => 'Boxing Day'],
            '12-31' => ['emoji' => '🎉', 'name' => 'New Year\'s Eve'],
        ];

        $key = Carbon::parse($date)->format('m-d');

        return $holidays[$key] ?? null;
    }

    protected function getDatesWithOverrides(): array
    {
        return VenueTimeSlot::query()
            ->select('booking_date')
            ->whereIn('schedule_template_id', $this->venue->scheduleTemplates()->pluck('id'))
            ->distinct()
            ->pluck('booking_date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->toArray();
    }

    protected function getFormattedDate(?string $date): string
    {
        if (blank($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('l, F j, Y');
        } catch (Exception) {
            return $date;
        }
    }

    public function render()
    {
        return view('livewire.venue.schedule-manager', [
            'formattedTime' => $this->getFormattedTime(),
        ]);
    }
}

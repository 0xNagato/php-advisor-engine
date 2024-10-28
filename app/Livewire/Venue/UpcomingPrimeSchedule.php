<?php

namespace App\Livewire\Venue;

use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VenueTimeSlot;
use Carbon\Carbon;
use Exception;
use Filament\Notifications\Notification;
use Ijpatricio\Mingle\Concerns\InteractsWithMingles;
use Ijpatricio\Mingle\Contracts\HasMingles;
use Illuminate\Support\Collection;
use Livewire\Component;

class UpcomingPrimeSchedule extends Component implements HasMingles
{
    use InteractsWithMingles;

    public const int DAYS_TO_DISPLAY = 30;

    private const string DATE_FORMAT = 'Y-m-d';

    private const string TIME_FORMAT = 'H:i:s';

    protected Venue $venue;

    protected Collection $upcomingDates;

    protected array $timeSlots = [];

    protected array $selectedTimeSlots = [];

    protected array $operatingHours;

    public function component(): string
    {
        return 'resources/js/Venue/UpcomingPrimeSchedule/index.js';
    }

    public function mingleData(): array
    {
        return [
            'earliestStartTime' => $this->operatingHours['earliest_start_time']->format('H:i'),
            'latestEndTime' => $this->operatingHours['latest_end_time']->format('H:i'),
            'upcomingDates' => $this->upcomingDates,
            'timeSlots' => $this->timeSlots,
            'selectedTimeSlots' => $this->selectedTimeSlots,
            'daysToDisplay' => self::DAYS_TO_DISPLAY,
            'detailedSchedule' => $this->venue->getDetailedSchedule(),
        ];
    }

    public function boot(): void
    {
        $this->venue = auth()->user()->venue;
        $this->operatingHours = $this->getOperatingHours();
        $this->upcomingDates = $this->getUpcomingDates();
        $this->generateTimeSlots();
        $this->initializeSelectedTimeSlots();
    }

    protected function getOperatingHours(): array
    {
        $hours = $this->venue->getOperatingHours();

        return [
            'earliest_start_time' => Carbon::parse($hours['earliest_start_time']),
            'latest_end_time' => Carbon::parse($hours['latest_end_time']),
        ];
    }

    protected function getUpcomingDates(): Collection
    {
        return collect(range(0, self::DAYS_TO_DISPLAY - 1))
            ->map(fn ($days) => Carbon::now()->addDays($days));
    }

    protected function generateTimeSlots(): void
    {
        $scheduleTemplates = $this->getScheduleTemplates();
        $venueTimeSlots = $this->getVenueTimeSlots();

        foreach ($this->upcomingDates as $date) {
            $slots = [];
            $currentTime = $date->copy()->setTimeFromTimeString($this->operatingHours['earliest_start_time']->format(self::TIME_FORMAT));
            $endTime = $date->copy()->setTimeFromTimeString($this->operatingHours['latest_end_time']->format(self::TIME_FORMAT));
            $dayOfWeek = strtolower((string) $date->format('l'));

            while ($currentTime->lessThan($endTime)) {
                $timeSlotStart = $currentTime->format(self::TIME_FORMAT);
                $timeSlotEnd = $currentTime->copy()->addMinutes(30)->format(self::TIME_FORMAT);

                $scheduleTemplate = $scheduleTemplates[$dayOfWeek]->firstWhere('start_time', $timeSlotStart);
                $override = $venueTimeSlots->where('schedule_template_id', $scheduleTemplate?->id)
                    ->firstWhere('booking_date', $date->format(self::DATE_FORMAT));

                $slots[] = [
                    'start' => $timeSlotStart,
                    'end' => $timeSlotEnd,
                    'is_checked' => $override ? $override->prime_time : ($scheduleTemplate->prime_time ?? false),
                    'override_id' => $override?->id,
                    'schedule_template_id' => $scheduleTemplate?->id,
                ];

                $currentTime->addMinutes(30);
            }

            $this->timeSlots[$date->format(self::DATE_FORMAT)] = $slots;
        }
    }

    protected function initializeSelectedTimeSlots(): void
    {
        $this->selectedTimeSlots = $this->upcomingDates->mapWithKeys(function ($date) {
            $dateString = $date->format(self::DATE_FORMAT);

            return [
                $dateString => collect($this->timeSlots[$dateString])
                    ->pluck('is_checked')
                    ->toArray(),
            ];
        })->toArray();
    }

    public function save(array $selectedTimeSlots): array
    {
        try {
            $this->selectedTimeSlots = $selectedTimeSlots;

            $scheduleTemplates = $this->getScheduleTemplates();
            $existingOverrides = $this->getExistingOverrides();
            [$updates, $inserts] = $this->prepareUpdatesAndInserts($scheduleTemplates, $existingOverrides);
            $this->performUpdates($updates);
            $this->performInserts($inserts);

            $this->sendSuccessNotification();

            return ['success' => true, 'message' => 'Prime schedule saved successfully.'];
        } catch (Exception $e) {
            report($e);
            $this->sendErrorNotification();

            return ['success' => false, 'message' => 'Error saving prime schedule.'];
        }
    }

    private function getScheduleTemplates(): Collection
    {
        return ScheduleTemplate::query()
            ->where('venue_id', $this->venue->id)
            ->get()
            ->groupBy('day_of_week');
    }

    private function getVenueTimeSlots(): Collection
    {
        $upcomingDates = $this->upcomingDates->map(fn ($date) => $date->format(self::DATE_FORMAT))->toArray();

        return VenueTimeSlot::query()
            ->whereIn('booking_date', $upcomingDates)
            ->whereIn('schedule_template_id', $this->venue->scheduleTemplates->pluck('id'))
            ->get();
    }

    private function getExistingOverrides(): Collection
    {
        return VenueTimeSlot::query()
            ->whereIn('booking_date', array_keys($this->selectedTimeSlots))
            ->whereIn('schedule_template_id', $this->venue->scheduleTemplates->pluck('id'))
            ->get()
            ->keyBy(fn ($item) => $item->schedule_template_id.'|'.$item->booking_date);
    }

    private function prepareUpdatesAndInserts(Collection $scheduleTemplates, Collection $existingOverrides): array
    {
        $updates = [];
        $inserts = [];

        foreach ($this->selectedTimeSlots as $date => $slots) {
            $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));

            foreach ($slots as $index => $isChecked) {
                $slot = $this->timeSlots[$date][$index];
                $timeKey = $slot['start'];

                if (isset($scheduleTemplates[$dayOfWeek])) {
                    $matchingTemplates = $scheduleTemplates[$dayOfWeek]->where('start_time', $timeKey);
                    foreach ($matchingTemplates as $scheduleTemplate) {
                        $overrideKey = $scheduleTemplate->id.'|'.$date;
                        $override = $existingOverrides[$overrideKey] ?? null;

                        if ($override) {
                            $updates[] = [
                                'id' => $override->id,
                                'prime_time' => $isChecked,
                            ];
                        } elseif (! $isChecked) {
                            $inserts[] = [
                                'schedule_template_id' => $scheduleTemplate->id,
                                'booking_date' => $date,
                                'prime_time' => $isChecked,
                            ];
                        }
                    }
                }
            }
        }

        return [$updates, $inserts];
    }

    private function performUpdates(array $updates): void
    {
        foreach ($updates as $update) {
            VenueTimeSlot::query()->where('id', $update['id'])->update(['prime_time' => $update['prime_time']]);
        }
    }

    private function performInserts(array $inserts): void
    {
        if (filled($inserts)) {
            VenueTimeSlot::query()->insert($inserts);
        }
    }

    private function sendSuccessNotification(): void
    {
        Notification::make()
            ->title('Prime schedule saved successfully.')
            ->success()
            ->send();
    }

    private function sendErrorNotification(): void
    {
        Notification::make()
            ->title('Error saving prime schedule.')
            ->danger()
            ->send();
    }
}

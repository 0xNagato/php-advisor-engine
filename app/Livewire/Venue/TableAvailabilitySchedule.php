<?php

namespace App\Livewire\Venue;

use App\Models\Venue;
use App\Services\ReservationHoursService;
use Carbon\Carbon;
use Exception;
use Filament\Notifications\Notification;
use Ijpatricio\Mingle\Concerns\InteractsWithMingles;
use Ijpatricio\Mingle\Contracts\HasMingles;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

class TableAvailabilitySchedule extends Component implements HasMingles
{
    use InteractsWithMingles;

    const int MAX_AVAILABLE_TABLES = 20;

    public Venue $venue;

    protected array $weeklySchedule = [];

    protected array $businessHours = [];

    public string $currentDay;

    protected $listeners = ['reservation-hours-updated' => '$refresh'];

    public function boot(): void
    {
        // $this->venue = auth()->user()->venue;
        $this->currentDay = strtolower(Carbon::now()->format('l'));
        $this->generateWeeklySchedule();
        $this->loadBusinessHours();
    }

    public function component(): string
    {
        return 'resources/js/Venue/TableAvailabilitySchedule/index.js';
    }

    public function mingleData(): array
    {
        return [
            'weeklySchedule' => $this->weeklySchedule,
            'openDays' => $this->venue->open_days,
            'currentDay' => $this->currentDay,
            'partySizes' => $this->venue->party_sizes,
            'businessHours' => $this->businessHours,
            'maxAvailableTables' => self::MAX_AVAILABLE_TABLES,
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
        $times = [];
        $schedules = $this->venue->scheduleTemplates()
            ->where('day_of_week', $day)
            ->where('is_available', true)
            ->get();

        foreach ($schedules as $schedule) {
            if ($schedule->party_size === 0) {
                continue;
            }

            if (! isset($times[$schedule->start_time])) {
                $times[$schedule->start_time] = [
                    'start' => $schedule->start_time,
                    'end' => Carbon::parse($schedule->start_time)->addMinutes(30)->format('H:i:s'),
                    'prime_time' => $schedule->prime_time,
                ];
            }
            $times[$schedule->start_time][$schedule->party_size] = $schedule->available_tables;
        }

        return array_values($times);
    }

    protected function loadBusinessHours(): void
    {
        $reservationHoursService = app(ReservationHoursService::class);
        $reservationHours = $reservationHoursService->loadHours($this->venue);

        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            $this->businessHours[$day] = [
                'start' => $reservationHours->startTimes[$day] ?? '',
                'end' => $reservationHours->endTimes[$day] ?? '',
            ];
        }
    }

    /**
     * @throws Throwable
     */
    public function saveAvailability(array $updatedSchedule): array
    {
        try {
            DB::beginTransaction();

            foreach ($updatedSchedule as $day => $slots) {
                if (is_array($slots)) {
                    $this->venue->scheduleTemplates()
                        ->where('day_of_week', $day)
                        ->get()
                        ->chunk(200)
                        ->each(function ($schedules) use ($slots) {
                            foreach ($schedules as $schedule) {
                                $slot = collect($slots)->firstWhere('start', $schedule->start_time);
                                if ($slot) {
                                    $schedule->available_tables = $slot[$schedule->party_size] ?? 0;
                                    $schedule->save();
                                }
                            }
                        });
                }
            }

            DB::commit();

            $this->generateWeeklySchedule();

            Notification::make()
                ->title('Table availability updated successfully.')
                ->success()
                ->send();

            return ['success' => true, 'message' => 'Table availability updated successfully.'];
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            Notification::make()
                ->title('Error updating table availability.')
                ->danger()
                ->send();

            return ['success' => false, 'message' => 'Error updating table availability.'];
        }
    }

    public function duplicateSchedule(array $updatedSchedule): array
    {
        try {
            DB::beginTransaction();

            $sourceDay = array_key_first($updatedSchedule);
            $sourceSlots = $updatedSchedule[$sourceDay];

            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $targetDay) {
                if ($targetDay === $sourceDay || $this->venue->open_days[$targetDay] !== 'open') {
                    continue;
                }

                $updatedSchedule[$targetDay] = $sourceSlots;
            }

            // Reuse existing save logic
            foreach ($updatedSchedule as $day => $slots) {
                if (is_array($slots)) {
                    $this->venue->scheduleTemplates()
                        ->where('day_of_week', $day)
                        ->get()
                        ->chunk(200)
                        ->each(function ($schedules) use ($slots) {
                            foreach ($schedules as $schedule) {
                                $slot = collect($slots)->firstWhere('start', $schedule->start_time);
                                if ($slot) {
                                    $schedule->available_tables = $slot[$schedule->party_size] ?? 0;
                                    $schedule->save();
                                }
                            }
                        });
                }
            }

            DB::commit();
            $this->generateWeeklySchedule();

            Notification::make()
                ->title('Schedule duplicated successfully.')
                ->success()
                ->send();

            return ['success' => true, 'message' => 'Schedule duplicated successfully.'];
        } catch (Exception $e) {
            DB::rollBack();
            report($e);

            Notification::make()
                ->title('Error duplicating schedule.')
                ->danger()
                ->send();

            return ['success' => false, 'message' => 'Error duplicating schedule.'];
        }
    }

    public function refresh(): array
    {
        $this->generateWeeklySchedule();
        $this->loadBusinessHours();

        return $this->mingleData();
    }
}

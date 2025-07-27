<?php

namespace App\Actions\Venue;

use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VenueTimeSlot;
use App\Services\CoverManagerService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class SyncCoverManagerAvailabilityAction
{
    use AsAction;

    public function handle(Venue $venue, Carbon $date, int $days = 1): array
    {
        // Check if venue has enabled CoverManager platform
        $platform = $venue->getPlatform('covermanager');

        if (! $platform || ! $platform->is_enabled) {
            return [
                'success' => false,
                'message' => 'CoverManager platform not enabled for venue',
                'stats' => $this->getEmptyStats(),
            ];
        }

        $coverManagerService = $venue->coverManager();

        if (! $coverManagerService) {
            return [
                'success' => false,
                'message' => 'CoverManager service not available',
                'stats' => $this->getEmptyStats(),
            ];
        }

        $stats = $this->getEmptyStats();

        try {
            $endDate = $date->copy()->addDays($days - 1);

            // Make single bulk API call for entire date range
            $calendarData = $coverManagerService->checkAvailabilityCalendar(
                $venue,
                $date,
                $endDate,
                'all',
                '1'
            );

            // Handle empty response or API errors
            if (empty($calendarData) || (isset($calendarData['resp']) && $calendarData['resp'] === 0)) {
                Log::warning("CoverManager calendar API returned no data for venue {$venue->id}", [
                    'venue_id' => $venue->id,
                    'date_range' => "{$date->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
                    'response' => $calendarData,
                ]);

                return [
                    'success' => false,
                    'message' => 'CoverManager API returned no data',
                    'stats' => $stats,
                ];
            }

            // Process each day in the range
            for ($i = 0; $i < $days; $i++) {
                $currentDate = $date->copy()->addDays($i);
                $dateKey = $currentDate->format('Y-m-d');

                // Get all schedule templates for this date
                $scheduleTemplates = $venue->scheduleTemplates()
                    ->where('day_of_week', strtolower($currentDate->format('l')))
                    ->where('is_available', true)
                    ->get();

                $stats['total_slots_analyzed'] += $scheduleTemplates->count();

                // For each schedule template, check availability in bulk calendar data
                foreach ($scheduleTemplates as $template) {
                    $slotResult = $this->processScheduleTemplate($venue, $template, $currentDate, $dateKey, $calendarData);
                    
                    if ($slotResult['override_created']) {
                        $stats['overrides_created']++;
                    }
                    
                    if ($slotResult['override_removed']) {
                        $stats['overrides_removed']++;
                    }
                    
                    if ($slotResult['skipped_human_override']) {
                        $stats['human_overrides_preserved']++;
                    }
                }
            }

            // Update last sync timestamp in platform configuration
            $platform->update(['last_synced_at' => now()]);

            // Create summary activity log instead of individual logs
            $this->logSyncSummary($venue, $date, $days, $stats);

            return [
                'success' => true,
                'message' => 'Sync completed successfully',
                'stats' => $stats,
            ];
        } catch (Throwable $e) {
            Log::error("Failed to sync venue {$venue->id} availability with CoverManager", [
                'error' => $e->getMessage(),
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
                'date' => $date->format('Y-m-d'),
                'days' => $days,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => $stats,
            ];
        }
    }

    protected function processScheduleTemplate(
        Venue $venue,
        ScheduleTemplate $template,
        Carbon $currentDate,
        string $dateKey,
        array $calendarData
    ): array {
        // Check if VenueTimeSlot already exists
        $existingSlot = VenueTimeSlot::query()
            ->where('schedule_template_id', $template->id)
            ->where('booking_date', $currentDate)
            ->first();

        // Skip if human-created override exists (check activity logs)
        if ($existingSlot && $this->isHumanCreatedSlot($venue, $existingSlot)) {
            return [
                'override_created' => false,
                'override_removed' => false,
                'skipped_human_override' => true,
            ];
        }

        // Parse CM bulk response to determine if venue has availability
        $hasCmAvailability = $this->parseCalendarAvailabilityResponse(
            $calendarData,
            $dateKey,
            $template
        );

        // Determine what prime_time should be based on CM availability
        $shouldBePrime = ! $hasCmAvailability; // No CM availability = Prime

        // Check if we need to create/update a venue time slot
        // Only create one if it differs from the template default
        if ($shouldBePrime !== $template->prime_time) {
            // Create or update VenueTimeSlot to override the template default
            VenueTimeSlot::query()->updateOrCreate(
                [
                    'schedule_template_id' => $template->id,
                    'booking_date' => $currentDate,
                ],
                [
                    'prime_time' => $shouldBePrime,
                    'is_available' => $template->is_available,
                    'available_tables' => $template->available_tables,
                    'price_per_head' => $template->price_per_head ?? 0,
                    'minimum_spend_per_guest' => $template->minimum_spend_per_guest ?? 0,
                    'prime_time_fee' => $template->prime_time_fee ?? 0,
                ]
            );

            return [
                'override_created' => true,
                'override_removed' => false,
                'skipped_human_override' => false,
            ];
        } else {
            // CM availability matches template default, remove any existing override
            if ($existingSlot && ! $this->isHumanCreatedSlot($venue, $existingSlot)) {
                $existingSlot->delete();

                return [
                    'override_created' => false,
                    'override_removed' => true,
                    'skipped_human_override' => false,
                ];
            }

            return [
                'override_created' => false,
                'override_removed' => false,
                'skipped_human_override' => false,
            ];
        }
    }

    /**
     * Check if a VenueTimeSlot was created by a human (not automated sync)
     */
    protected function isHumanCreatedSlot(Venue $venue, VenueTimeSlot $slot): bool
    {
        // Check if there are any activity logs for this slot that indicate human interaction
        $humanActions = [
            'override_update',
            'calendar_bulk_update',
            'make_day_prime',
            'make_day_non_prime',
            'mark_day_sold_out',
            'close_day',
            'set_price_per_head_for_day',
        ];

        return Activity::query()
            ->where('subject_type', Venue::class)
            ->where('subject_id', $venue->id)
            ->whereJsonContains('properties->venue_time_slot_id', $slot->id)
            ->whereIn('description', $humanActions)
            ->exists();
    }

    /**
     * Parse CoverManager calendar availability response to determine if venue has availability
     * for a specific date and schedule template
     */
    protected function parseCalendarAvailabilityResponse(array $response, string $dateKey, ScheduleTemplate $template): bool
    {
        // Handle empty response or API errors
        if (empty($response)) {
            return false;
        }

        // Check if response indicates API failure
        if (isset($response['resp']) && $response['resp'] === 0) {
            return false;
        }

        // Check if calendar data exists
        if (! isset($response['calendar'])) {
            return false;
        }

        // Check if data exists for this specific date
        if (! isset($response['calendar'][$dateKey])) {
            return false;
        }

        $dayData = $response['calendar'][$dateKey];
        $partySize = (string) $template->party_size;
        $time = Carbon::parse($template->start_time)->format('H:i');

        // Check availability in people array (party size specific)
        if (isset($dayData['people'][$partySize][$time])) {
            return true;
        }

        // Check availability in hours array (time specific, any party size)
        if (isset($dayData['hours'][$time])) {
            return true;
        }

        // Check if there's a generic "slots" availability for this date
        if (isset($dayData['slots']) && is_array($dayData['slots'])) {
            foreach ($dayData['slots'] as $slot) {
                if (isset($slot['availability']) && $slot['availability'] === true) {
                    return true;
                }
            }
        }

        // Check if time slot appears as a direct key with availability data
        if (isset($dayData[$time])) {
            $timeSlotData = $dayData[$time];
            if (isset($timeSlotData['availability']) && $timeSlotData['availability'] === true) {
                return true;
            }
        }

        return false;
    }

    protected function logSyncSummary(Venue $venue, Carbon $date, int $days, array $stats): void
    {
        activity()
            ->performedOn($venue)
            ->withProperties([
                'sync_type' => 'covermanager_availability_bulk',
                'date_range' => [
                    'start_date' => $date->format('Y-m-d'),
                    'end_date' => $date->copy()->addDays($days - 1)->format('Y-m-d'),
                    'days_count' => $days,
                ],
                'stats' => $stats,
                'sync_method' => 'bulk_calendar',
            ])
            ->log('CoverManager availability sync completed');
    }

    protected function getEmptyStats(): array
    {
        return [
            'total_slots_analyzed' => 0,
            'overrides_created' => 0,
            'overrides_removed' => 0,
            'human_overrides_preserved' => 0,
        ];
    }
}
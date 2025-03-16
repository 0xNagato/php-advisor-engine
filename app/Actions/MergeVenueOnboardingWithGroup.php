<?php

namespace App\Actions;

use App\Data\VenueContactData;
use App\Enums\VenueStatus;
use App\Models\Region;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use App\Models\VenueOnboarding;
use App\Models\VenueOnboardingLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MergeVenueOnboardingWithGroup
{
    /**
     * Merge a venue onboarding with an existing venue group
     *
     * @param  VenueOnboarding  $onboarding  The onboarding request to process
     * @param  VenueGroup  $venueGroup  The venue group to merge with
     * @param  User  $processedBy  The admin user processing the request
     * @param  string  $notes  Processing notes
     * @param  array  $venueDefaults  Default settings for venues
     *
     * @throws ValidationException
     */
    public function execute(
        VenueOnboarding $onboarding,
        VenueGroup $venueGroup,
        User $processedBy,
        string $notes,
        array $venueDefaults = []
    ): void {
        throw_unless($venueGroup->primary_manager_id, ValidationException::withMessages([
            'venue_group_id' => 'Selected venue group has no primary manager',
        ]));

        // Get the primary manager
        $manager = $venueGroup->primaryManager;
        throw_unless($manager, ValidationException::withMessages([
            'venue_group_id' => 'Primary manager not found',
        ]));

        DB::transaction(function () use ($onboarding, $venueGroup, $manager, $processedBy, $notes, $venueDefaults) {
            // Create venues for each location in the onboarding
            $venues = [];
            $venueData = [];

            foreach ($onboarding->locations as $location) {
                $venue = Venue::query()->create([
                    'name' => $location->name,
                    'venue_group_id' => $venueGroup->id,
                    'user_id' => $manager->id,
                    'region' => $location->region,
                    'timezone' => $location->region ? Region::getTimezoneForRegion($location->region) : null,
                    'prime_hours' => $location->prime_hours ?? [],
                    'business_hours' => $location->booking_hours ?? [],
                    'use_non_prime_incentive' => $location->use_non_prime_incentive,
                    'non_prime_per_diem' => $location->non_prime_per_diem,
                    'logo_path' => $location->logo_path,
                    'status' => VenueStatus::DRAFT,
                    'payout_venue' => $venueDefaults['payout_venue'] ?? 60,
                    'booking_fee' => $venueDefaults['booking_fee'] ?? 200,
                    'contact_phone' => $onboarding->phone,
                    'primary_contact_name' => $onboarding->first_name.' '.$onboarding->last_name,
                    'contacts' => [
                        VenueContactData::from([
                            'contact_name' => $onboarding->first_name.' '.$onboarding->last_name,
                            'contact_phone' => $onboarding->phone,
                            'use_for_reservations' => true,
                        ])->toArray(),
                    ],
                ]);

                // Update the onboarding location with the created venue ID
                $location->update([
                    'created_venue_id' => $venue->id,
                ]);

                // Ensure schedule templates exist for the venue
                $this->ensureScheduleTemplatesExist($venue);

                // Set up booking hours first (this affects available slots)
                $this->setupBookingHours($venue, $location);

                // Then set up prime time slots
                $this->setupPrimeTimeSlots($venue, $location);

                $venues[] = $venue->id;
                $venueData[] = [
                    'name' => $venue->name,
                ];
            }

            // Get all currently allowed venue IDs for the primary manager
            $currentAllowedVenueIds = json_decode($venueGroup->managers->firstWhere('id', $manager->id)?->pivot->allowed_venue_ids ?? '[]', true);

            // Merge with new venue IDs and update the pivot
            $allVenueIds = array_unique(array_merge($currentAllowedVenueIds, $venues));

            // Update all managers in the venue group to have access to the new venues
            foreach ($venueGroup->managers as $groupManager) {
                $managerAllowedVenueIds = json_decode($groupManager->pivot->allowed_venue_ids ?? '[]', true);
                $managerUpdatedVenueIds = array_unique(array_merge($managerAllowedVenueIds, $venues));

                $venueGroup->managers()->updateExistingPivot(
                    $groupManager->id,
                    ['allowed_venue_ids' => json_encode($managerUpdatedVenueIds)]
                );
            }

            // Enhance notes with merge information
            $enhancedNotes = "[MERGED WITH EXISTING GROUP] This onboarding was merged with venue group '{$venueGroup->name}' (ID: {$venueGroup->id}). Added ".count($venues)." venue(s) to the group with primary manager {$manager->name}.\n\n{$notes}";

            // Mark the onboarding as processed
            $onboarding->markAsProcessed($processedBy, $enhancedNotes);
        });
    }

    /**
     * Set up booking hours for a venue based on the onboarding location
     */
    private function setupBookingHours(Venue $venue, VenueOnboardingLocation $location): void
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // First set all slots to unavailable
        $venue->scheduleTemplates()->update(['is_available' => false]);

        foreach ($daysOfWeek as $dayOfWeek) {
            // Prioritize location booking_hours if available, otherwise use venue's business_hours
            $bookingHours = $location->booking_hours[$dayOfWeek] ?? $venue->business_hours[$dayOfWeek] ?? null;

            if (! $bookingHours || ($bookingHours['closed'] ?? false)) {
                continue;
            }

            $startTime = Carbon::createFromFormat('H:i:s', $bookingHours['start']);
            $endTime = Carbon::createFromFormat('H:i:s', $bookingHours['end']);

            // Make slots available within booking hours
            $venue->scheduleTemplates()
                ->where('day_of_week', $dayOfWeek)
                ->get()
                ->each(function ($template) use ($startTime, $endTime) {
                    $slotTime = Carbon::createFromFormat('H:i:s', $template->start_time);

                    if ($slotTime->greaterThanOrEqualTo($startTime) &&
                        $slotTime->lessThan($endTime)) {
                        $template->update(['is_available' => true]);
                    }
                });
        }
    }

    /**
     * Set up prime time slots for a venue based on the onboarding location
     */
    private function setupPrimeTimeSlots(Venue $venue, VenueOnboardingLocation $location): void
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // First set all slots to non-prime
        $venue->scheduleTemplates()
            ->where('is_available', true)  // Only update available slots
            ->update(['prime_time' => false]);

        foreach ($daysOfWeek as $dayOfWeek) {
            $primeHours = $location->prime_hours[$dayOfWeek] ?? $venue->prime_hours[$dayOfWeek] ?? [];

            if (! is_array($primeHours) || blank($primeHours)) {
                continue;
            }

            // Get all the prime time slots for this day
            $primeTimeSlots = array_keys(array_filter($primeHours, fn ($value) => $value === true));

            foreach ($primeTimeSlots as $timeSlot) {
                $venue->scheduleTemplates()
                    ->where('day_of_week', $dayOfWeek)
                    ->where('start_time', $timeSlot)
                    ->where('is_available', true)  // Only update available slots
                    ->update(['prime_time' => true]);
            }
        }
    }

    /**
     * Ensure schedule templates exist for a venue
     */
    private function ensureScheduleTemplatesExist(Venue $venue): void
    {
        // Check if any schedule templates exist
        $existingCount = $venue->scheduleTemplates()->count();

        if ($existingCount > 0) {
            return;
        }

        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $timeSlots = [
            '09:00:00', '09:30:00', '10:00:00', '10:30:00', '11:00:00', '11:30:00',
            '12:00:00', '12:30:00', '13:00:00', '13:30:00', '14:00:00', '14:30:00',
            '15:00:00', '15:30:00', '16:00:00', '16:30:00', '17:00:00', '17:30:00',
            '18:00:00', '18:30:00', '19:00:00', '19:30:00', '20:00:00', '20:30:00',
            '21:00:00', '21:30:00', '22:00:00', '22:30:00', '23:00:00', '23:30:00',
        ];

        $templates = [];

        foreach ($daysOfWeek as $dayOfWeek) {
            foreach ($timeSlots as $timeSlot) {
                $templates[] = [
                    'venue_id' => $venue->id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $timeSlot,
                    'is_available' => false,
                    'prime_time' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Bulk insert all templates
        DB::table('venue_schedule_templates')->insert($templates);
    }
}

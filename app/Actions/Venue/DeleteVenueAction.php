<?php

namespace App\Actions\Venue;

use App\Enums\BookingStatus;
use App\Models\User;
use App\Models\Venue;
use DB;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

class DeleteVenueAction
{
    use AsAction;

    /**
     * Execute the action to delete a venue.
     *
     * @param  Venue  $venue  The venue to delete
     * @return bool True if the venue was deleted successfully
     *
     * @throws RuntimeException If the venue has bookings or user is not authorized
     */
    public function handle(Venue $venue): bool
    {
        $this->authorize($venue);
        $this->ensureVenueHasNoBookings($venue);
        $this->validateVenueGroup($venue);

        try {
            DB::transaction(function () use ($venue) {
                // Check if this is the only venue in a venue group
                $venueGroup = null;
                $deleteGroup = false;

                if ($venue->venue_group_id !== null) {
                    $venueGroup = $venue->venueGroup;
                    $deleteGroup = $venueGroup && $venueGroup->venues()->count() === 1;
                }

                // First, handle any bookings related to this venue
                $this->handleRelatedBookings($venue);

                // Delete all schedules and templates for the venue first
                $venue->scheduleTemplates()->delete();

                // Delete any special pricing
                $venue->specialPricing()->delete();

                // Handle foreign key constraints with various tables

                // Known specific tables with foreign keys to venues
                $this->handleVenueOnboardingLocations($venue);
                $this->handlePotentialForeignKeys($venue);

                // Log venue deletion
                activity()
                    ->performedOn($venue)
                    ->withProperties([
                        'venue_id' => $venue->id,
                        'venue_name' => $venue->name,
                        'deleted_by' => Auth::id(),
                        'deleted_by_name' => Auth::user()->name ?? 'System',
                    ])
                    ->log('Venue deleted');

                // Delete the venue
                $venue->delete();

                // If this was the only venue in the group, delete the venue group too
                if ($deleteGroup && $venueGroup) {
                    // Get all managers before deletion
                    $managers = $venueGroup->managers()->get();

                    // Get concierges for this venue group - concierges belong to venue groups
                    $concierges = DB::table('concierges')
                        ->where('venue_group_id', $venueGroup->id)
                        ->get();

                    // Store manager IDs before detaching
                    $managerIds = $managers->pluck('id')->toArray();

                    // Detach managers from the venue group
                    $venueGroup->managers()->detach();

                    // Suspend managers who are only associated with this venue group
                    foreach ($managers as $manager) {
                        // After detaching, check if manager is associated with any other venue groups
                        $otherGroupsCount = DB::table('venue_group_managers')
                            ->where('user_id', $manager->id)
                            ->count();

                        if ($otherGroupsCount === 0) {
                            // This manager is not part of any other venue group, suspend their account
                            $manager->update([
                                'suspended_at' => now(),
                            ]);

                            // Log the suspension
                            activity()
                                ->performedOn($manager)
                                ->withProperties([
                                    'venue_group_id' => $venueGroup->id,
                                    'venue_group_name' => $venueGroup->name,
                                    'reason' => 'Venue group deleted',
                                ])
                                ->log('User suspended due to venue group deletion');

                            // Remove venue_manager role if this was their only venue group
                            if ($manager->hasRole('venue_manager')) {
                                $manager->removeRole('venue_manager');
                            }
                        }
                    }

                    // Store concierge data for post-processing
                    $conciergeData = [];
                    foreach ($concierges as $concierge) {
                        $conciergeData[] = [
                            'id' => $concierge->id,
                            'user_id' => $concierge->user_id,
                        ];
                    }

                    // Update all concierges at once to set venue_group_id to null
                    if (! empty($conciergeData)) {
                        DB::table('concierges')
                            ->where('venue_group_id', $venueGroup->id)
                            ->update(['venue_group_id' => null]);
                    }

                    // Process concierges - check for need to suspend user accounts
                    foreach ($conciergeData as $data) {
                        // Check if concierge is associated with any other venue group
                        $otherGroupsCount = DB::table('concierges')
                            ->whereNotNull('venue_group_id')
                            ->where('user_id', $data['user_id'])
                            ->count();

                        // Get the user associated with this concierge
                        $user = User::find($data['user_id']);

                        if ($otherGroupsCount === 0 && $user) {
                            // This concierge is not part of any other venue group, suspend their account
                            $user->update([
                                'suspended_at' => now(),
                            ]);

                            // Log suspension reason since there's no suspended_reason field
                            activity()
                                ->performedOn($user)
                                ->withProperties([
                                    'reason' => 'Venue group deleted',
                                ])
                                ->log('User suspended due to venue group deletion');

                            // Remove concierge role if this was their only venue group association
                            if ($user->hasRole('concierge')) {
                                $user->removeRole('concierge');
                            }
                        }
                    }

                    // Log venue group deletion
                    activity()
                        ->withProperties([
                            'venue_group_id' => $venueGroup->id,
                            'venue_group_name' => $venueGroup->name,
                            'deleted_by' => Auth::id(),
                            'deleted_by_name' => Auth::user()->name ?? 'System',
                            'primary_manager_id' => $venueGroup->primary_manager_id,
                            'suspended_managers' => $managers->filter(function ($manager) {
                                // Count managers who were suspended
                                return $manager->suspended_at !== null;
                            })->count(),
                            'total_managers' => count($managerIds),
                        ])
                        ->log('Venue group deleted');

                    // Finally delete the venue group
                    $venueGroup->delete();
                }
            });

            return true;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to delete venue: '.$e->getMessage());
        }
    }

    /**
     * Validate if venue can be removed from venue group.
     *
     * @param  Venue  $venue  The venue to check
     *
     * @throws RuntimeException If the venue is the only one in its group
     */
    private function validateVenueGroup(Venue $venue): void
    {
        // No validation needed - we'll handle deleting the venue group if it's the only venue
    }

    /**
     * Authorize the action.
     *
     * @param  Venue  $venue  The venue to delete
     *
     * @throws RuntimeException If the user is not authorized
     */
    private function authorize(Venue $venue): void
    {
        throw_unless(in_array(Auth::id(), config('app.god_ids', [])), new RuntimeException('You are not authorized to delete venues.'));
    }

    /**
     * Ensure the venue has no bookings that would prevent deletion.
     *
     * @param  Venue  $venue  The venue to check
     *
     * @throws RuntimeException If the venue has bookings
     */
    private function ensureVenueHasNoBookings(Venue $venue): void
    {
        // Find all schedule template IDs for this venue
        $scheduleTemplateIds = $venue->scheduleTemplates()->pluck('id')->toArray();

        if (empty($scheduleTemplateIds)) {
            return; // No schedule templates, so no bookings
        }

        // Check for reporting bookings
        $hasReportingBookings = DB::table('bookings')
            ->whereIn('schedule_template_id', $scheduleTemplateIds)
            ->whereIn('status', BookingStatus::REPORTING_STATUSES)
            ->exists();

        throw_if($hasReportingBookings, new RuntimeException(
            'Cannot delete venue with confirmed, completed, or refunded bookings. '.
            'Please cancel or remove all bookings first.'
        ));
    }

    /**
     * Handle foreign key references in venue_onboarding_locations
     *
     * @param  Venue  $venue  The venue to handle
     */
    private function handleVenueOnboardingLocations(Venue $venue): void
    {
        if (Schema::hasTable('venue_onboarding_locations')) {
            DB::table('venue_onboarding_locations')
                ->where('created_venue_id', $venue->id)
                ->update(['created_venue_id' => null]);
        }
    }

    /**
     * Handle other potential foreign key constraints
     *
     * @param  Venue  $venue  The venue to handle
     */
    private function handlePotentialForeignKeys(Venue $venue): void
    {
        // Based on actual database schema and model relationships
        $potentialTables = [
            'schedule_templates' => 'venue_id',          // Direct FK to venues
            'special_pricing_venues' => 'venue_id',      // Direct FK to venues
            'special_requests' => 'venue_id',            // Direct FK to venues
            'venue_group_managers' => 'current_venue_id', // Direct FK to venues
            'venue_invoices' => 'venue_id',              // Direct FK to venues
            'venue_onboarding_locations' => 'created_venue_id', // Direct FK to venues
            // Note: bookings relate to venues through schedule_templates, not directly
            // Note: schedule_with_bookings is a view, not a table with FKs
        ];

        foreach ($potentialTables as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                // Try to set to null if the column is nullable
                try {
                    DB::table($table)
                        ->where($column, $venue->id)
                        ->update([$column => null]);
                } catch (Exception $e) {
                    // If not nullable, attempt to delete the records
                    try {
                        DB::table($table)
                            ->where($column, $venue->id)
                            ->delete();
                    } catch (Exception $innerE) {
                        // Log the error but continue with other tables
                        \Log::warning("Could not handle references in table {$table}: {$innerE->getMessage()}");
                    }
                }
            }
        }

        // Handle JSON arrays that might contain venue IDs
        $this->handleJsonVenueReferences($venue);
    }

    /**
     * Handle JSON fields that might contain venue IDs
     *
     * @param  Venue  $venue  The venue to handle
     */
    private function handleJsonVenueReferences(Venue $venue): void
    {
        // Handle venue_group_managers.allowed_venue_ids
        if (Schema::hasTable('venue_group_managers') && Schema::hasColumn('venue_group_managers', 'allowed_venue_ids')) {
            $managers = DB::table('venue_group_managers')->get();

            foreach ($managers as $manager) {
                $allowedVenues = json_decode($manager->allowed_venue_ids ?? '[]', true);

                if (in_array($venue->id, $allowedVenues)) {
                    // Remove this venue ID from the array
                    $allowedVenues = array_diff($allowedVenues, [$venue->id]);

                    // Update the record
                    DB::table('venue_group_managers')
                        ->where('id', $manager->id)
                        ->update(['allowed_venue_ids' => json_encode($allowedVenues)]);
                }
            }
        }

        // Handle concierges.allowed_venue_ids
        if (Schema::hasTable('concierges') && Schema::hasColumn('concierges', 'allowed_venue_ids')) {
            $concierges = DB::table('concierges')->get();

            foreach ($concierges as $concierge) {
                $allowedVenues = json_decode($concierge->allowed_venue_ids ?? '[]', true);

                if (in_array($venue->id, $allowedVenues)) {
                    // Remove this venue ID from the array
                    $allowedVenues = array_diff($allowedVenues, [$venue->id]);

                    // Update the record
                    DB::table('concierges')
                        ->where('id', $concierge->id)
                        ->update(['allowed_venue_ids' => json_encode($allowedVenues)]);
                }
            }
        }
    }

    /**
     * Handle any bookings related to this venue through schedule templates
     *
     * @param  Venue  $venue  The venue to handle
     */
    private function handleRelatedBookings(Venue $venue): void
    {
        // Find all schedule template IDs for this venue
        $scheduleTemplateIds = $venue->scheduleTemplates()->pluck('id')->toArray();

        if (empty($scheduleTemplateIds)) {
            return;
        }

        // Check for non-reporting bookings (which can be safely deleted)
        $nonReportingBookings = DB::table('bookings')
            ->whereIn('schedule_template_id', $scheduleTemplateIds)
            ->whereIn('status', BookingStatus::NON_REPORTING_STATUSES)
            ->get();

        // Delete these bookings as they don't affect reporting
        if ($nonReportingBookings->isNotEmpty()) {
            DB::table('bookings')
                ->whereIn('id', $nonReportingBookings->pluck('id')->toArray())
                ->delete();

            // Log the deletion
            \Log::info('Deleted '.$nonReportingBookings->count().' non-reporting bookings for venue '.$venue->id);
        }

        // Note: We already check for reporting bookings in ensureVenueHasNoBookings(),
        // so we don't need to do it again here
    }
}

<?php

namespace App\Actions\Venue;

use App\Enums\EarningType;
use App\Models\Earning;
use App\Models\Venue;
use App\Models\VenueGroup;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateVenueGroupEarnings
{
    use AsAction;

    /**
     * Update earnings for venues in a venue group to assign them to the primary manager.
     *
     * @param  VenueGroup  $venueGroup  The venue group
     * @param  Collection<Venue>|array<Venue>  $venues  The venues to update earnings for
     * @return int Number of earnings updated
     */
    public function handle(VenueGroup $venueGroup, Collection|array $venues): int
    {
        $earningsUpdated = 0;

        // Check if the venue group has a primary manager
        if (! $venueGroup->primary_manager_id) {
            return $earningsUpdated;
        }

        // Get the primary manager
        $primaryManager = $venueGroup->primaryManager;

        if (! $primaryManager) {
            return $earningsUpdated;
        }

        // Specify the earning types we want to update (venue and venue_paid)
        $earningTypes = [EarningType::VENUE->value, EarningType::VENUE_PAID->value];

        // Process each venue
        foreach ($venues as $venue) {
            // Get all bookings for this venue
            $bookingIds = $venue->bookings()->pluck('bookings.id')->toArray();

            if (blank($bookingIds)) {
                continue;
            }

            // Get earnings for these bookings of the specified types
            $venueEarnings = Earning::query()->whereIn('type', $earningTypes)
                ->whereIn('booking_id', $bookingIds)
                ->get();

            // Update each earning to assign it to the primary manager
            foreach ($venueEarnings as $earning) {
                if ($earning->user_id !== $primaryManager->id) {
                    $earning->user_id = $primaryManager->id;
                    $earning->save();
                    $earningsUpdated++;
                }
            }
        }

        return $earningsUpdated;
    }
}

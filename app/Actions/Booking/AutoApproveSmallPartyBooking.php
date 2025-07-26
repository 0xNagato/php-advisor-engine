<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class AutoApproveSmallPartyBooking
{
    use AsAction;

    public const int MAX_AUTO_APPROVAL_PARTY_SIZE = 7;

    /**
     * Auto-approve a booking if it qualifies for automatic approval.
     *
     * This is only called when platform sync has already succeeded,
     * so we don't need to check platform sync status.
     *
     * @param  Booking  $booking  The booking to potentially auto-approve
     * @return bool Whether the booking was auto-approved
     */
    public function handle(Booking $booking): bool
    {
        // Check if booking qualifies for auto-approval
        if (! $this->shouldAutoApprove($booking)) {
            return false;
        }

        // Check if already venue confirmed
        if ($booking->venue_confirmed_at !== null) {
            Log::info("Booking {$booking->id} already venue confirmed, skipping auto-approval");

            return false;
        }

        // Auto-approve the booking
        $booking->update([
            'venue_confirmed_at' => now(),
        ]);

        // Log the auto-approval
        activity()
            ->performedOn($booking)
            ->withProperties([
                'venue_confirmed_at' => $booking->venue_confirmed_at,
                'auto_approved' => true,
                'party_size' => $booking->guest_count,
                'venue_platforms' => $booking->venue->platforms->pluck('platform_type')->toArray(),
            ])
            ->log('Booking auto-approved for small party with platform integration');

        Log::info("Auto-approved booking {$booking->id} for {$booking->guest_count} guests at {$booking->venue->name}", [
            'booking_id' => $booking->id,
            'venue_id' => $booking->venue->id,
            'venue_name' => $booking->venue->name,
            'party_size' => $booking->guest_count,
            'guest_name' => $booking->guest_first_name.' '.$booking->guest_last_name,
            'booking_date' => Carbon::parse($booking->booking_at)->format('Y-m-d'),
            'booking_time' => Carbon::parse($booking->booking_at)->format('H:i'),
            'venue_platforms' => $booking->venue->platforms->pluck('platform_type')->toArray(),
        ]);

        // Send auto-approval notification to venue contacts
        SendAutoApprovalNotificationToVenueContacts::run($booking);

        return true;
    }

    /**
     * Determine if a booking should be auto-approved.
     *
     * Platform sync success is assumed since this action is only
     * called after successful platform sync.
     */
    private function shouldAutoApprove(Booking $booking): bool
    {
        // Party size must be 7 or under
        if ($booking->guest_count > self::MAX_AUTO_APPROVAL_PARTY_SIZE) {
            Log::debug("Booking {$booking->id} not auto-approved: party size {$booking->guest_count} exceeds limit of ".self::MAX_AUTO_APPROVAL_PARTY_SIZE);

            return false;
        }

        // Venue must have at least one enabled platform (restoo or covermanager)
        $enabledPlatforms = $booking->venue->platforms()
            ->where('is_enabled', true)
            ->whereIn('platform_type', ['restoo', 'covermanager'])
            ->exists();

        if (! $enabledPlatforms) {
            Log::debug("Booking {$booking->id} not auto-approved: venue has no enabled platforms (restoo/covermanager)");

            return false;
        }

        Log::debug("Booking {$booking->id} qualifies for auto-approval", [
            'party_size' => $booking->guest_count,
            'enabled_platforms' => $booking->venue->platforms()
                ->where('is_enabled', true)
                ->whereIn('platform_type', ['restoo', 'covermanager'])
                ->pluck('platform_type')
                ->toArray(),
        ]);

        return true;
    }
}

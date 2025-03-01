<?php

namespace App\Actions\Booking\Authorization;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class CanModifyBooking
{
    use AsAction;

    public const MINUTES_BEFORE_BOOKING_TO_MODIFY = 30;

    public function handle(Booking $booking, User $user): bool
    {
        // Super admin can always modify
        $isSuperAdmin = $user->hasActiveRole('super_admin');
        if ($isSuperAdmin) {
            return true;
        }

        // Basic conditions: non-prime and correct status
        if ($booking->is_prime ||
            ! in_array($booking->status, [
                BookingStatus::CONFIRMED,
                BookingStatus::VENUE_CONFIRMED,
            ])) {
            return false;
        }

        $isBookingConcierge = $user->hasActiveRole('concierge') &&
                             $user->id === $booking->concierge?->user_id;

        // Must be the booking's concierge
        if (! $isBookingConcierge) {
            return false;
        }

        // Check time restrictions for non-super admins
        $bookingTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $booking->booking_at,
            $booking->venue->timezone
        );
        $now = now($booking->venue->timezone);

        // Cannot modify within 30 minutes before booking
        if ($now->diffInMinutes($bookingTime, false) <= self::MINUTES_BEFORE_BOOKING_TO_MODIFY) {
            return false;
        }

        // Cannot modify after booking has started
        if ($now > $bookingTime) {
            return false;
        }

        return true;
    }
}

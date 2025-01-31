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
        // Basic conditions: non-prime and correct status
        if ($booking->is_prime ||
            ! in_array($booking->status, [
                BookingStatus::CONFIRMED,
                BookingStatus::VENUE_CONFIRMED,
            ])) {
            return false;
        }

        $isSuperAdmin = $user->hasActiveRole('super_admin');
        $isBookingConcierge = $user->hasActiveRole('concierge') &&
                             $user->id === $booking->concierge?->user_id;

        // Must be super admin or the booking's concierge
        if (! $isSuperAdmin && ! $isBookingConcierge) {
            return false;
        }

        // Check 30-minute restriction for all users
        $bookingTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $booking->booking_at,
            $booking->venue->timezone
        );
        $now = now($booking->venue->timezone);

        if ($now->diffInMinutes($bookingTime, false) <= self::MINUTES_BEFORE_BOOKING_TO_MODIFY) {
            return false;
        }

        return true;
    }
}

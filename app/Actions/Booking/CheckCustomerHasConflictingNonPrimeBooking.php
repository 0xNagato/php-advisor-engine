<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Traits\FormatsPhoneNumber;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class CheckCustomerHasConflictingNonPrimeBooking
{
    use AsAction;
    use FormatsPhoneNumber;

    public const int BOOKING_WINDOW_HOURS = 2;

    public function handle(string $phoneNumber, Carbon $bookingAt): ?Booking
    {
        // Create the time window around the requested booking time
        $internationalPhoneNumber = $this->getInternationalFormattedPhoneNumber($phoneNumber);
        $windowStart = $bookingAt->copy()->subHours(self::BOOKING_WINDOW_HOURS);
        $windowEnd = $bookingAt->copy()->addHours(self::BOOKING_WINDOW_HOURS);

        return Booking::query()
            ->where('guest_phone', $internationalPhoneNumber)
            ->where('is_prime', false)
            ->whereNotIn('status', [
                BookingStatus::CANCELLED,
                BookingStatus::REFUNDED,
                BookingStatus::ABANDONED,
            ])
            ->where('booking_at', '>=', $windowStart)
            ->where('booking_at', '<=', $windowEnd)->first();
    }
}

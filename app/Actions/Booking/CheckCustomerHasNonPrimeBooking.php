<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Traits\FormatsPhoneNumber;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class CheckCustomerHasNonPrimeBooking
{
    use AsAction;
    use FormatsPhoneNumber;

    public function handle(string $phoneNumber, string $bookingDate, ?string $timezone = null): bool
    {
        // Create the date range in local timezone
        $date = Carbon::parse($bookingDate, $timezone ?: 'UTC');
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        $internationalPhoneNumber = $this->getInternationalFormattedPhoneNumber($phoneNumber);

        // Check for existing non-prime bookings
        return Booking::query()
            ->where('guest_phone', $internationalPhoneNumber)
            ->where('is_prime', false)
            ->whereNotIn('status', [
                BookingStatus::CANCELLED,
                BookingStatus::REFUNDED,
                BookingStatus::ABANDONED,
            ])
            ->whereBetween('booking_at', [$startOfDay, $endOfDay])
            ->exists();
    }
}

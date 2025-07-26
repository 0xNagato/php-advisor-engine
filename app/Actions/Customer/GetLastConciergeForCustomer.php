<?php

namespace App\Actions\Customer;

use App\Models\Booking;
use Lorisleiva\Actions\Concerns\AsAction;

class GetLastConciergeForCustomer
{
    use AsAction;

    /**
     * Get the most recent concierge that served a customer based on their phone number.
     *
     * This checks the booking history to find the last concierge who either:
     * - Booked the customer through the platform (reservation_hub/availability_calendar)
     * - Had their VIP code used by the customer (api with vip_code)
     *
     * @param  string  $customerPhone  The customer's phone number (should be in international format)
     * @return int|null The concierge ID, or null if no history found
     */
    public function handle(string $customerPhone): ?int
    {
        $lastBooking = Booking::query()->where('guest_phone', $customerPhone)
            ->whereNotNull('concierge_id')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastBooking?->concierge_id;
    }
}

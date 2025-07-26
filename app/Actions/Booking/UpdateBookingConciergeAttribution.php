<?php

namespace App\Actions\Booking;

use App\Actions\Customer\GetLastConciergeForCustomer;
use App\Models\Booking;
use App\Models\VipCode;
use App\Services\Booking\BookingCalculationService;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateBookingConciergeAttribution
{
    use AsAction;

    /**
     * Update a booking's concierge attribution based on customer history.
     *
     * This is called during booking completion when we have the customer's phone number.
     * For API bookings without VIP codes OR with house VIP codes (HOME, DIRECT), we'll
     * check if the customer has been served by a different concierge previously and
     * update the attribution accordingly.
     *
     * When concierge attribution changes, we also recalculate earnings to ensure
     * the correct concierge receives the commission.
     *
     * @param  Booking  $booking  The booking to potentially update
     * @param  string  $customerPhone  Customer's phone number for attribution lookup
     * @return bool Whether the booking was updated
     */
    public function handle(Booking $booking, string $customerPhone): bool
    {
        // Only apply customer attribution for API bookings
        if ($booking->source !== 'api') {
            return false;
        }

        // Skip attribution for regular VIP codes (not house codes)
        if ($booking->vip_code_id && ! $this->isHouseVipCode($booking->vipCode)) {
            return false;
        }

        // Check customer attribution history
        $attributedConciergeId = GetLastConciergeForCustomer::run($customerPhone);

        // If we found a different concierge in customer history, update the booking
        if ($attributedConciergeId && $attributedConciergeId !== $booking->concierge_id) {
            // Store original concierge for comparison
            $originalConciergeId = $booking->concierge_id;

            // Update concierge attribution
            $booking->update([
                'concierge_id' => $attributedConciergeId,
            ]);

            // Recalculate earnings with new concierge
            $this->recalculateBookingEarnings($booking);

            return true;
        }

        return false;
    }

    /**
     * Recalculate booking earnings after concierge attribution changes.
     *
     * This ensures the correct concierge receives the commission when
     * attribution is updated based on customer history.
     *
     * Process:
     * 1. Delete existing earnings records
     * 2. Recalculate earnings with new concierge
     * 3. Confirm earnings if booking is confirmed
     */
    private function recalculateBookingEarnings(Booking $booking): void
    {
        // Delete existing earnings records since concierge has changed
        $booking->earnings()->delete();

        // Recalculate earnings with the new concierge
        // This will create new earning records and update booking earnings fields
        app(BookingCalculationService::class)->calculateEarnings($booking);

        // If booking is confirmed, mark earnings as confirmed
        if (! in_array($booking->status, ['cancelled', 'refunded']) && $booking->confirmed_at) {
            $booking->earnings()->update(['confirmed_at' => $booking->confirmed_at]);
        }
    }

    /**
     * Check if a VIP code is a house VIP code that should allow customer attribution.
     *
     * House VIP codes (like HOME, DIRECT) are used for tracking purposes but should
     * still allow customer attribution based on history, unlike regular VIP codes
     * which have specific concierge attribution.
     *
     * @param  VipCode  $vipCode  The VIP code to check
     * @return bool Whether this is a house VIP code
     */
    private function isHouseVipCode($vipCode): bool
    {
        $houseVipCodes = config('app.house.vip_codes', []);

        return in_array($vipCode->code, $houseVipCodes);
    }
}

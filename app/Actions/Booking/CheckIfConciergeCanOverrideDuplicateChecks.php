<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Lorisleiva\Actions\Concerns\AsAction;

class CheckIfConciergeCanOverrideDuplicateChecks
{
    use AsAction;

    public function handle(Booking $booking, string $phoneNumber): bool
    {
        // Ensure the booking has an associated concierge
        if (! $booking->concierge) {
            return false;
        }

        $concierge = $booking->concierge;

        // Check if the concierge can override duplicate checks
        if (! $concierge->can_override_duplicate_checks) {
            return false;
        }

        // Get the concierge's phone number from the related user
        $conciergePhone = $concierge->user->phone;

        // Compare the provided phone number with the concierge's own phone number
        if ($phoneNumber !== $conciergePhone) {
            return false;
        }

        return true;
    }
}

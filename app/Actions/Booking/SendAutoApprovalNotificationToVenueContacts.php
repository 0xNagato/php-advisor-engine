<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Notifications\Booking\VenueContactBookingAutoApproved;
use Exception;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class SendAutoApprovalNotificationToVenueContacts
{
    use AsAction;

    /**
     * Send auto-approval notification to venue contacts.
     *
     * @param  Booking  $booking  The auto-approved booking
     */
    public function handle(Booking $booking): void
    {
        $venue = $booking->venue;
        $contacts = $venue->contacts->filter(fn ($contact) => $contact->use_for_reservations);

        if ($contacts->count() === 0) {
            Log::warning("No venue contacts found for auto-approval notification for booking {$booking->id} at {$venue->name}");
        }

        foreach ($contacts as $contact) {
            try {
                $contact->notify(new VenueContactBookingAutoApproved($booking));
            } catch (Exception $e) {
                Log::error("Failed to send auto-approval notification to venue contact for booking {$booking->id}: {$e->getMessage()}", [
                    'booking_id' => $booking->id,
                    'venue_id' => $venue->id,
                    'venue_name' => $venue->name,
                    'contact_phone' => $contact->contact_phone,
                    'contact_email' => $contact->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

<?php

namespace App\Actions\Booking;

use App\Data\VenueContactData;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\Booking\AdminBookingConfirmed;
use App\Notifications\Booking\VenueContactBookingConfirmed;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Action;
use ShortURL;

/**
 * This action handles sending booking confirmations to the venue contacts.
 *
 * When a booking is confirmed, this action retrieves the contacts associated with the venue,
 * generates a confirmation URL, and sends a notification to each contact that is marked to receive reservations.
 */
class SendConfirmationToVenueContacts extends Action
{
    /**
     * Execute the action to send booking confirmation notifications to venue contacts.
     *
     * @throws ShortURLException If there is an error generating the short URL for the confirmation.
     */
    public function handle(Booking $booking, bool $reminder = false): void
    {
        /** @var Collection<VenueContactData> $contacts */
        $contacts = $booking->venue->contacts ?? collect();

        $url = route('venues.confirm', ['booking' => $booking]);
        $confirmationUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        $mins = app()->isLocal() || $booking->is_prime ? 0 : 5;
        $delay = now()->addMinutes($mins);
        $contacts->filter(fn ($contact) => $contact->use_for_reservations)
            ->each(fn ($contact) => $contact->notify((new VenueContactBookingConfirmed(
                booking: $booking,
                confirmationUrl: $confirmationUrl,
                reminder: $reminder
            ))->delay($delay)));

        if ($mins) {
            logger()
                ->info("Notifications for booking #$booking->id will be sent in $mins: ".$delay->format('H:i'));
        }

        if (! $reminder) {
            $admin = User::query()->where('email', 'andru.weir@gmail.com')->first();
            if ($admin) {
                $admin->notify(new AdminBookingConfirmed($booking, $confirmationUrl));
            }
        }
    }
}

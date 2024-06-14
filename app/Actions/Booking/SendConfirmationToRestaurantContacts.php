<?php

namespace App\Actions\Booking;

use App\Data\RestaurantContactData;
use App\Models\Booking;
use App\Notifications\Booking\RestaurantContactBookingConfirmed;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Action;
use ShortURL;

/**
 * This action handles sending booking confirmations to the restaurant contacts.
 *
 * When a booking is confirmed, this action retrieves the contacts associated with the restaurant,
 * generates a confirmation URL, and sends a notification to each contact that is marked to receive reservations.
 */
class SendConfirmationToRestaurantContacts extends Action
{
    /**
     * Execute the action to send booking confirmation notifications to restaurant contacts.
     *
     * @throws ShortURLException If there is an error generating the short URL for the confirmation.
     */
    public function handle(Booking $booking): void
    {
        /** @var Collection<RestaurantContactData> $contacts */
        $contacts = $booking->restaurant->contacts ?? collect();

        $url = route('restaurants.confirm', ['token' => $booking->uuid]);
        $confirmationUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        foreach ($contacts as $contact) {
            if ($contact->use_for_reservations) {
                $booking->notify(new RestaurantContactBookingConfirmed(
                    contact: $contact,
                    confirmationUrl: $confirmationUrl,
                ));
            }
        }
    }
}

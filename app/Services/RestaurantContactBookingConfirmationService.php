<?php

namespace App\Services;

use App\Data\RestaurantContactData;
use App\Models\Booking;
use App\Notifications\RestaurantBookingPaid;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Support\Collection;
use ShortURL;

class RestaurantContactBookingConfirmationService
{
    /**
     * @throws ShortURLException
     */
    public function sendConfirmation(Booking $booking): void
    {
        /** @var Collection<RestaurantContactData> $contacts */
        $contacts = $booking->restaurant->contacts;

        $url = route('restaurants.confirm', ['token' => $booking->uuid]);
        $confirmationUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        foreach ($contacts as $contact) {
            if ($contact->use_for_reservations) {
                $contact->toNotifiable()->notify(new RestaurantBookingPaid($booking, $confirmationUrl));
            }
        }
    }
}

<?php

namespace App\Services;

use App\Data\RestaurantContactData;
use App\Models\Booking;
use App\Notifications\Booking\RestaurantContactBookingConfirmed;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Carbon\CarbonInterface;
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
        $contacts = $booking->restaurant->contacts ?? [];

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

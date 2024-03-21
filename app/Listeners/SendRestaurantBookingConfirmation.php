<?php

namespace App\Listeners;

use App\Data\RestaurantContactData;
use App\Events\BookingPaid;
use App\Notifications\RestaurantBookingPaid;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use ShortURL;

class SendRestaurantBookingConfirmation implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @throws ShortURLException
     */
    public function handle(BookingPaid $event): void
    {
        /** @var Collection<RestaurantContactData> $contacts */
        $contacts = $event->booking->restaurant->contacts;

        $url = route('restaurants.confirm', ['token' => $event->booking->uuid]);
        $confirmationUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        foreach ($contacts as $contact) {
            if ($contact->use_for_reservations) {
                $contact->toNotifiable()->notify(new RestaurantBookingPaid($event->booking, $confirmationUrl));
            }
        }
    }
}

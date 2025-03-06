<?php

namespace App\Actions\Booking;

use App\Data\VenueContactData;
use App\Models\BookingModificationRequest;
use App\Notifications\Booking\VenueContactModificationRequested;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Lorisleiva\Actions\Action;
use ShortURL;

class SendModificationRequestToVenueContacts extends Action
{
    /**
     * Execute the action to send modification request notifications to venue contacts.
     *
     * @throws ShortURLException If there is an error generating the short URL for the confirmation.
     */
    public function handle(BookingModificationRequest $modificationRequest): void
    {
        /** @var Collection<VenueContactData> $contacts */
        $contacts = $modificationRequest->booking->venue->contacts ?? collect();

        $url = URL::signedRoute('venue.modification-request', [
            'modificationRequest' => $modificationRequest,
        ]);

        $confirmationUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        foreach ($contacts as $contact) {
            if ($contact->use_for_reservations) {
                $modificationRequest->notify(new VenueContactModificationRequested(
                    contact: $contact,
                    confirmationUrl: $confirmationUrl,
                ));
            }
        }
    }
}

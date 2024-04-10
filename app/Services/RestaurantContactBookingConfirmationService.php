<?php

namespace App\Services;

use App\Data\RestaurantContactData;
use App\Models\Booking;
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
                $this->sendSMS($booking, $confirmationUrl, $contact);
            }
        }
    }

    public function sendSMS(Booking $booking, string $confirmationUrl, RestaurantContactData $contact): void
    {
        $bookingDate = $this->getFormattedDate($booking->booking_at);

        $bookingTime = $booking->booking_at->format('g:ia');

        app(SimpleTextingAdapter::class)->sendMessage(
            $contact->contact_phone,
            "PRIMA Reservation - $bookingDate at $bookingTime, $booking->guest_name, $booking->guest_count guests, $booking->guest_phone."
        );

    }

    private function getFormattedDate(CarbonInterface $date): string
    {
        $today = now();
        $tomorrow = now()->addDay();

        if ($date->isSameDay($today)) {
            return 'today';
        }

        if ($date->isSameDay($tomorrow)) {
            return 'tomorrow';
        }

        return $date->format('l \\t\\h\\e jS');
    }
}

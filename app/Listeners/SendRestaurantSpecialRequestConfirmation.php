<?php

namespace App\Listeners;

use App\Data\RestaurantContactData;
use App\Events\SpecialRequestCreated;
use App\Services\SmsService;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;
use Spatie\LaravelData\DataCollection;

class SendRestaurantSpecialRequestConfirmation
{
    const int SECURE_LINK_LIFE_IN_DAYS = 7;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // Constructor left intentionally blank
    }

    /**
     * Handle the event.
     *
     * @throws ShortURLException
     */
    public function handle(SpecialRequestCreated $event): void
    {
        $restaurant = $event->specialRequest->restaurant;

        // Generate the confirmation URL
        $confirmationUrl = $this->generateConfirmationUrl($event->specialRequest->uuid);

        // Format the booking date
        $bookingDate = $this->getFormattedDate(Carbon::parse($event->specialRequest->booking_date));

        // Format the booking time
        $bookingTime = Carbon::parse($event->specialRequest->booking_time)->format('g:ia');

        // Create the message to be sent
        $message = $this->createMessage($event, $bookingDate, $bookingTime, $confirmationUrl);

        // Send the message to contacts
        $this->sendMessagesToContacts($restaurant->contacts, $message);
    }

    /**
     * Generate the confirmation URL.
     *
     * @throws ShortURLException
     */
    private function generateConfirmationUrl(string $uuid): string
    {
        $url = URL::signedRoute('restaurants.confirm-special-request', ['token' => $uuid], now()->addDays(self::SECURE_LINK_LIFE_IN_DAYS));

        return ShortURL::destinationUrl($url)->make()->default_short_url;
    }

    /**
     * Create the message to be sent to contacts.
     */
    private function createMessage(SpecialRequestCreated $event, string $bookingDate, string $bookingTime, string $confirmationUrl): string
    {
        $bookingDate = ucfirst($bookingDate);
        $specialRequest = $event->specialRequest;
        $currency = $specialRequest->restaurant->inRegion->currency;
        $minimumSpend = moneyWithoutCents($specialRequest->minimum_spend * 100, $currency);
        $customerName = $specialRequest->customer_name;
        $partySize = $specialRequest->party_size;

        return "PRIMA Special Request from $customerName: $bookingDate at $bookingTime, $partySize guests, Min. Spend $minimumSpend. Click here to view the request: $confirmationUrl";
    }

    /**
     * Send the message to the restaurant contacts.
     *
     * @param  DataCollection<RestaurantContactData>  $contacts
     */
    private function sendMessagesToContacts(DataCollection $contacts, string $message): void
    {
        foreach ($contacts as $contact) {
            if ($contact->use_for_reservations) {
                app(SmsService::class)->sendMessage($contact->contact_phone, $message);
            }
        }
    }

    /**
     * Get a formatted date string.
     */
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

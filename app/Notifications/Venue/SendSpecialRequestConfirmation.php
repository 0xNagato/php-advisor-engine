<?php

namespace App\Notifications\Venue;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\Models\SpecialRequest;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class SendSpecialRequestConfirmation extends Notification
{
    use Queueable;

    public const int SECURE_LINK_LIFE_IN_DAYS = 7;

    public string $message;

    public string $confirmationUrl;

    public string $bookingDate;

    public string $bookingTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SpecialRequest $specialRequest
    ) {
        // Generate the confirmation URL
        $this->confirmationUrl = $this->generateConfirmationUrl($specialRequest->uuid);

        // Format the booking date
        $this->bookingDate = Carbon::toNotificationFormat(Carbon::parse($specialRequest->booking_date));

        // Format the booking time
        $this->bookingTime = Carbon::parse($specialRequest->booking_time)->format('g:ia');

        // Create the message to be sent
        $this->message = $this->createMessage();
    }

    public function toSms(VenueContactData $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->contact_phone,
            text: $this->message,
        );
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(VenueContactData $notifiable): array
    {
        return $notifiable->toChannel();
    }

    /**
     * Generate the confirmation URL.
     *
     * @throws ShortURLException
     */
    private function generateConfirmationUrl(string $uuid): string
    {
        $url = URL::signedRoute('venues.confirm-special-request', ['token' => $uuid], now()->addDays(self::SECURE_LINK_LIFE_IN_DAYS));

        return ShortURL::destinationUrl($url)->make()->default_short_url;
    }

    /**
     * Create the message to be sent to contacts.
     */
    private function createMessage(): string
    {
        $bookingDate = ucfirst($this->bookingDate);
        $currency = $this->specialRequest->venue->inRegion->currency;
        $minimumSpend = moneyWithoutCents($this->specialRequest->minimum_spend * 100, $currency);
        $customerName = $this->specialRequest->customer_name;
        $partySize = $this->specialRequest->party_size;

        return "*PRIMA* Special Request from $customerName: $bookingDate at $this->bookingTime, $partySize guests, Min. Spend $minimumSpend. Click $this->confirmationUrl to view this request.";
    }
}

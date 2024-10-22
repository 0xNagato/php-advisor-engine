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
     *
     * @throws ShortURLException
     */
    public function __construct(
        public SpecialRequest $specialRequest
    ) {
        $this->confirmationUrl = $this->generateConfirmationUrl($specialRequest->uuid);
        $this->bookingDate = Carbon::toNotificationFormat(Carbon::parse($specialRequest->booking_date));
        $this->bookingTime = Carbon::parse($specialRequest->booking_time)->format('g:ia');
    }

    public function toSms(VenueContactData $notifiable): SmsData
    {
        $bookingDate = ucfirst($this->bookingDate);
        $currency = $this->specialRequest->venue->inRegion->currency;
        $minimumSpend = moneyWithoutCents($this->specialRequest->minimum_spend * 100, $currency);
        $customerName = $this->specialRequest->customer_name;
        $partySize = $this->specialRequest->party_size;

        return new SmsData(
            phone: $notifiable->contact_phone,
            templateKey: 'venue_special_request_confirmation',
            templateData: [
                'customer_name' => $customerName,
                'booking_date' => $bookingDate,
                'booking_time' => $this->bookingTime,
                'party_size' => $partySize,
                'minimum_spend' => $minimumSpend,
                'confirmation_url' => $this->confirmationUrl,
            ]
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
}

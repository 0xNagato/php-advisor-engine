<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use ShortURL;

class CustomerReminder extends Notification
{
    use Queueable;

    public function __construct(public readonly Booking $booking) {}

    public function via(): array
    {
        return [SmsNotificationChannel::class];
    }

    /**
     * @throws ShortURLException
     */
    public function toSms(): SmsData
    {
        $invoiceUrl = ShortURL::destinationUrl(route('customer.invoice', $this->booking->uuid))
            ->make()
            ->default_short_url;

        return new SmsData(
            phone: $this->booking->guest_phone,
            templateKey: 'customer_booking_reminder',
            templateData: [
                'venue_name' => $this->booking->venue->name,
                'link' => $invoiceUrl,
            ]
        );
    }
}

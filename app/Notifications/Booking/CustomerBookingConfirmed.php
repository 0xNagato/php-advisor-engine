<?php

/** @noinspection PhpUnused */
/** @noinspection PhpUnusedParameterInspection */

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use ShortURL;

class CustomerBookingConfirmed extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [
            SmsNotificationChannel::class,
        ];
    }

    /**
     * @throws ShortURLException
     */
    public function toSMS(Booking $notifiable): SmsData
    {
        $templateKey = $notifiable->is_prime ? 'customer_booking_confirmed_prime' : 'customer_booking_confirmed_non_prime';

        return new SmsData(
            phone: $notifiable->guest_phone,
            templateKey: $templateKey,
            templateData: [
                'venue_name' => $notifiable->venue->name,
                'booking_date' => Carbon::toNotificationFormat($notifiable->booking_at),
                'booking_time' => $notifiable->booking_at->format('g:ia'),
                'guest_count' => $notifiable->guest_count,
                'invoice_url' => ShortURL::destinationUrl(route('customer.invoice', $notifiable->uuid))->make()->default_short_url,
                'concierge_name' => $notifiable->concierge->user->name,
            ]
        );
    }
}

<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CustomerBookingRequestReceived extends Notification
{
    use Queueable;

    public function __construct() {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return [SmsNotificationChannel::class];
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @throws ShortURLException
     */
    public function toSMS(Booking $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->guest_phone,
            templateKey: 'customer_booking_received_non_prime_big_group',
            templateData: [
                'venue_name' => $notifiable->venue?->name,
                'booking_date' => $notifiable->booking_at->format('D M jS'),
                'booking_time' => $notifiable->booking_at->format('g:ia'),
                'guest_count' => $notifiable->guest_count,
            ]
        );
    }
}

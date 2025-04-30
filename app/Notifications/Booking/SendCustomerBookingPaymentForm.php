<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendCustomerBookingPaymentForm extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $url,
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

    public function toSms(Booking $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->guest_phone,
            templateKey: 'customer_booking_payment_form',
            templateData: [
                'venue_name' => $notifiable->venue->name,
                'payment_url' => $this->url,
                'booking_date' => $notifiable->booking_at->format('D M jS'),
                'booking_time' => $notifiable->booking_at->format('g:ia'),
            ]
        );
    }
}

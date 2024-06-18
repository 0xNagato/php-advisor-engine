<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConfirmReservation extends Notification
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
        $message = "Your reservation at {$notifiable->restaurant->restaurant_name} is pending. Please click $this->url to secure your booking within the next 5 minutes.";

        return new SmsData(
            phone: $notifiable->guest_phone,
            text: $message,
        );
    }
}

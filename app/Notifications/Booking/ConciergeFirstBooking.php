<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConciergeFirstBooking extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return [SmsNotificationChannel::class];
    }

    public function toSMS(object $notifiable): SmsData
    {
        return new SmsData(
            phone: $this->booking->concierge->user->phone,
            templateKey: 'concierge_first_booking',
            templateData: [
                'guest_name' => $this->booking->guest_name,
                'venue_name' => $this->booking->venue->name,
            ]
        );
    }
}

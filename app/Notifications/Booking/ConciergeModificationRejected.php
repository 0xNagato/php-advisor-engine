<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\BookingModificationRequest;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ConciergeModificationRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via(object $notifiable): array
    {
        return [SmsNotificationChannel::class];
    }

    public function toSMS(BookingModificationRequest $notifiable): SmsData
    {
        $booking = $notifiable->booking;

        return new SmsData(
            phone: $booking->concierge->user->phone,
            templateKey: 'concierge_modification_rejected',
            templateData: [
                'venue_name' => $booking->venue->name,
            ]
        );
    }
}

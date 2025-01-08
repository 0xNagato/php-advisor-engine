<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\BookingModificationRequest;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CustomerModificationApproved extends Notification implements ShouldQueue
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
            phone: $booking->guest_phone,
            templateKey: 'customer_modification_approved',
            templateData: [
                'venue_name' => $booking->venue->name,
            ]
        );
    }
}

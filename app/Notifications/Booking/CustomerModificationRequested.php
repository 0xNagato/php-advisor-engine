<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\BookingModificationRequest;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CustomerModificationRequested extends Notification implements ShouldQueue
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
        $requestedBy = $notifiable->requestedBy;

        return new SmsData(
            phone: $booking->guest_phone,
            templateKey: 'customer_modification_requested',
            templateData: [
                'concierge_name' => $requestedBy ? $requestedBy->name : 'PRIMA',
            ]
        );
    }
}

<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CustomerBookingRefunded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Booking $booking
    ) {}

    public function via($notifiable): array
    {
        return [
            SmsNotificationChannel::class,
        ];
    }

    public function toSms($notifiable): SmsData
    {
        return new SmsData(
            phone: $this->booking->guest_phone,
            templateKey: 'customer_booking_refunded',
            templateData: [
                'guest_name' => $this->booking->guest_first_name,
                'amount' => money($this->booking->total_with_tax_in_cents, $this->booking->currency),
                'venue_name' => $this->booking->venue->name,
            ]
        );
    }
}

<?php

namespace App\Notifications;

use App\Data\SmsData;
use App\Models\Booking;
use App\Models\User;
use App\NotificationsChannels\SmsNotificationChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * VenueFailedToConfirmBooking
 *
 * This notification is sent to super admins when a venue fails to confirm a booking within the expected timeframe.
 * It alerts the admins about the unconfirmed booking, providing details such as the venue name, booking ID, date, and time.
 * This allows admins to take appropriate action, such as following up with the venue or making alternative arrangements.
 *
 * The notification is sent via SMS to ensure prompt attention to the issue.
 */
class VenueFailedToConfirmBooking extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return [
            SmsNotificationChannel::class,
        ];
    }

    public function toSms(User $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->phone,
            templateKey: 'admin_venue_failed_to_confirm_booking',
            templateData: [
                'venue_name' => $this->booking->venue->name,
                'booking_id' => $this->booking->id,
                'booking_date' => Carbon::toNotificationFormat($this->booking->booking_at),
                'booking_time' => $this->booking->booking_at->format('g:ia'),
            ]
        );
    }
}

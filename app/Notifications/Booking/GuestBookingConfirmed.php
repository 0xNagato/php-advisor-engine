<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\NotificationsChannels\SmsNotificationChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use ShortURL;

class GuestBookingConfirmed extends Notification
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

    public function toSMS(Booking $notifiable): SmsData
    {
        $bookingDate = Carbon::toNotificationFormat($notifiable->booking_at);

        $bookingTime = $notifiable->booking_at->format('g:ia');

        $invoiceUrl = ShortURL::destinationUrl(route('customer.invoice', $notifiable->uuid))->make()->default_short_url;

        if ($notifiable->is_prime) {
            $message = "PRIMA reservation at {$notifiable->venue->name} $bookingDate at $bookingTime with {$notifiable->guest_count} guests. View your invoice at $invoiceUrl.";
        } else {
            $message = "Hello from PRIMA VIP! Your reservation at {$notifiable->venue->name} $bookingDate at $bookingTime has been booked by {$notifiable->concierge->user->name} and is now confirmed. Please arrive within 15 minutes of your reservation or your table may be released. Thank you for booking with us!";
        }

        return new SmsData(
            phone: $notifiable->guest_phone,
            text: $message
        );
    }
}

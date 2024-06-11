<?php

namespace App\Notifications;

use App\Models\Booking;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;
use ShortURL;

class CustomerBookingPaid extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Booking $booking)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TwilioChannel::class];
    }

    /**
     * @throws ShortURLException
     */
    public function toTwilio(object $notifiable): TwilioSmsMessage|TwilioMessage
    {
        $bookingDate = Carbon::toNotificationFormat($this->booking->booking_at);

        $bookingTime = $this->booking->booking_at->format('g:ia');

        $invoiceUrl = ShortURL::destinationUrl(route('customer.invoice', $this->booking->uuid))->make()->default_short_url;

        $message = "PRIMA reservation at {$this->booking->restaurant->restaurant_name} $bookingDate at $bookingTime with {$this->booking->guest_count} guests. View your invoice at $invoiceUrl.";

        return (new TwilioSmsMessage())
            ->content($message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

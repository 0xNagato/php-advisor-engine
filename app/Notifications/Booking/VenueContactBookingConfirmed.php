<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VenueContactBookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public VenueContactData $contact,
        public string $confirmationUrl,
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
        return $this->contact->toChannel();
    }

    public function toSMS(Booking $notifiable): SmsData
    {
        $bookingDate = Carbon::toNotificationFormat($notifiable->booking_at);
        $bookingTime = $notifiable->booking_at->format('g:ia');

        return new SmsData(
            phone: $this->contact->contact_phone,
            text: "*PRIMA* Booking: $bookingDate @ $bookingTime, $notifiable->guest_name, $notifiable->guest_count guests, $notifiable->guest_phone. Click $this->confirmationUrl to confirm."
        );
    }
}

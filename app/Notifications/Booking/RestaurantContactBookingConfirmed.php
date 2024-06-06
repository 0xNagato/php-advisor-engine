<?php

namespace App\Notifications\Booking;

use App\Data\RestaurantContactData;
use App\Data\SmsData;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RestaurantContactBookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public RestaurantContactData $contact,
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
            text: "PRIMA Reservation - $bookingDate at $bookingTime, $notifiable->guest_name, $notifiable->guest_count guests, $notifiable->guest_phone. Confirm the reservation by clicking here $this->confirmationUrl."
        );
    }
}

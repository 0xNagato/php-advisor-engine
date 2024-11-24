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
        return new SmsData(
            phone: $this->contact->contact_phone,
            templateKey: 'venue_contact_booking_confirmed',
            templateData: [
                'venue_name' => $notifiable->venue->name,
                'booking_date' => Carbon::toNotificationFormat($notifiable->booking_at),
                'booking_time' => $notifiable->booking_at->format('g:ia'),
                'guest_name' => $notifiable->guest_name,
                'guest_count' => $notifiable->guest_count,
                'guest_phone' => $notifiable->guest_phone,
                'confirmation_url' => $this->confirmationUrl,
            ]
        );
    }
}

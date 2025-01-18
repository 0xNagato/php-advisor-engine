<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\Models\Booking;
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
        $templateKey = filled($notifiable->notes)
            ? 'venue_contact_booking_confirmed_notes'
            : 'venue_contact_booking_confirmed';

        $templateData = [
            'venue_name' => $notifiable->venue->name,
            'booking_date' => $notifiable->booking_at->format('M jS'),
            'booking_time' => $notifiable->booking_at->format('g:ia'),
            'guest_name' => $notifiable->guest_name,
            'guest_count' => $notifiable->guest_count,
            'guest_phone' => $notifiable->guest_phone,
            'confirmation_url' => $this->confirmationUrl,
        ];

        if (filled($notifiable->notes)) {
            $templateData['notes'] = $notifiable->notes;
        }

        return new SmsData(
            phone: $this->contact->contact_phone,
            templateKey: $templateKey,
            templateData: $templateData,
        );
    }
}

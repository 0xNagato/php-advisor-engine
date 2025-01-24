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

    private function formatBookingDate(Carbon $date, Booking $notifiable): string
    {
        // Create booking date with venue timezone
        $bookingDate = Carbon::create(
            $date->year,
            $date->month,
            $date->day,
            $date->hour,
            $date->minute,
            $date->second,
            $notifiable->venue->timezone
        );

        $venueToday = now()->setTimezone($notifiable->venue->timezone);

        if ($bookingDate->isSameDay($venueToday)) {
            return 'Today, '.$bookingDate->format('M jS');
        }

        if ($bookingDate->isSameDay($venueToday->copy()->addDay())) {
            return 'Tomorrow, '.$bookingDate->format('M jS');
        }

        return $bookingDate->format('M jS');
    }

    public function toSMS(Booking $notifiable): SmsData
    {
        $templateKey = filled($notifiable->notes)
            ? 'venue_contact_booking_confirmed_notes'
            : 'venue_contact_booking_confirmed';

        $templateData = [
            'venue_name' => $notifiable->venue->name,
            'booking_date' => $this->formatBookingDate($notifiable->booking_at, $notifiable),
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

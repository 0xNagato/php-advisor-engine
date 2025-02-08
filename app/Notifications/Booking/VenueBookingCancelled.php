<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VenueBookingCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VenueContactData $contact,
    ) {}

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
        return new SmsData(
            phone: $this->contact->contact_phone,
            templateKey: 'venue_booking_cancelled',
            templateData: [
                'guest_name' => $notifiable->guest_name,
                'guest_phone' => $notifiable->guest_phone,
                'venue_name' => $notifiable->venue->name,
                'booking_date' => $this->formatBookingDate($notifiable->booking_at, $notifiable),
            ]
        );
    }
}

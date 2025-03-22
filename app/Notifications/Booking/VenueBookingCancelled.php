<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VenueBookingCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {}

    public function via(VenueContactData $notifiable): array
    {
        return $notifiable->toChannel();
    }

    private function formatBookingDate(Carbon $date): string
    {
        // Create booking date with venue timezone
        $bookingDate = Carbon::create(
            $date->year,
            $date->month,
            $date->day,
            $date->hour,
            $date->minute,
            $date->second,
            $this->booking->venue->timezone
        );

        $venueToday = now()->setTimezone($this->booking->venue->timezone);

        if ($bookingDate->isSameDay($venueToday)) {
            return 'Today, '.$bookingDate->format('M jS');
        }

        if ($bookingDate->isSameDay($venueToday->copy()->addDay())) {
            return 'Tomorrow, '.$bookingDate->format('M jS');
        }

        return $bookingDate->format('M jS');
    }

    public function toSMS(VenueContactData $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->contact_phone,
            templateKey: 'venue_booking_cancelled',
            templateData: [
                'guest_name' => $this->booking->guest_name,
                'guest_phone' => $this->booking->guest_phone,
                'venue_name' => $this->booking->venue->name,
                'booking_date' => $this->formatBookingDate($this->booking->booking_at),
            ]
        );
    }

    public function toMail(): MailMessage
    {
        $bookingDate = $this->formatBookingDate($this->booking->booking_at);
        $bookingTime = $this->booking->booking_at->format('g:ia');

        return (new MailMessage)
            ->from('prima@primavip.co', 'PRIMA Reservation Platform')
            ->subject("PRIMA Notice: Booking Canceled - {$this->booking->venue->name}")
            ->greeting('Booking Canceled')
            ->line("A booking has been canceled at {$this->booking->venue->name}.")
            ->line('**Booking Details:**')
            ->line("Customer: {$this->booking->guest_name}")
            ->line("Customer phone: {$this->booking->guest_phone}")
            ->line("Date: {$bookingDate}")
            ->line("Time: {$bookingTime}")
            ->line('Please update your records. Thank you.');
    }
}

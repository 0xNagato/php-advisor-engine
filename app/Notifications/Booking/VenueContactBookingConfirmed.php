<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VenueContactBookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Booking $booking,
        public string $confirmationUrl,
        public bool $reminder = false,
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

    public function toSMS(object $notifiable): SmsData
    {
        $baseTemplate = $this->reminder
            ? 'venue_contact_booking_confirmed_reminder'
            : 'venue_contact_booking_confirmed';

        $templateKey = filled($this->booking->notes)
            ? $baseTemplate.'_notes'
            : $baseTemplate;

        $templateData = [
            'venue_name' => $this->booking->venue->name,
            'booking_date' => $this->formatBookingDate($this->booking->booking_at),
            'booking_time' => $this->booking->booking_at->format('g:ia'),
            'guest_name' => $this->booking->guest_name,
            'guest_count' => $this->booking->guest_count,
            'guest_phone' => $this->booking->guest_phone,
            'confirmation_url' => $this->confirmationUrl,
        ];

        if (filled($this->booking->notes)) {
            $templateData['notes'] = $this->booking->notes;
        }

        return new SmsData(
            phone: $notifiable->contact_phone,
            templateKey: $templateKey,
            templateData: $templateData,
        );
    }

    public function toMail(): MailMessage
    {
        $bookingDate = $this->formatBookingDate($this->booking->booking_at);
        $bookingTime = $this->booking->booking_at->format('g:ia');

        $notes = $this->booking->notes ?? 'NA';

        return (new MailMessage)
            ->from('prima@primavip.co', 'PRIMA Reservation Platform')
            ->subject("New Booking Confirmation - {$this->booking->venue->name}")
            ->greeting('New Booking Confirmation')
            ->line("A new booking has been confirmed at {$this->booking->venue->name}.")
            ->line('**Booking Details:**')
            ->line("Date: {$bookingDate}")
            ->line("Time: {$bookingTime}")
            ->line("Party Size: {$this->booking->guest_count}")
            ->line("Customer: {$this->booking->guest_first_name} {$this->booking->guest_last_name}")
            ->line("Customer phone: {$this->booking->guest_phone}")
            ->line("Notes: {$notes}")
            ->line('**Confirmation Link:**')
            ->action('Confirm', $this->confirmationUrl);
    }
}

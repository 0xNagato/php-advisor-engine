<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\Enums\VenueType;
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

    private function formatBookingTime(Carbon $date): string
    {
        // Handle special experiences for Ibiza Hike Station
        if ($this->booking->venue->venue_type == VenueType::HIKE_STATION) {
            $hour = $date->hour;

            // Determine hike type based on time of day (Ibiza sunset ~9:30 PM)
            if ($hour >= 6 && $hour < 14) {
                return 'Morning Hike';
            } else {
                // Anything 2 PM and later is sunset hike (ends around sunset time)
                return 'Sunset Hike';
            }
        }

        return $date->format('g:ia');
    }

    private function formatBookingDateTime(Carbon $date): string
    {
        $formattedDate = $this->formatBookingDate($date);

        // Handle special experiences for Ibiza Hike Station
        if ($this->booking->venue->venue_type == VenueType::HIKE_STATION) {
            $experienceName = $this->formatBookingTime($date);

            return "{$formattedDate} - {$experienceName}";
        }

        $formattedTime = $this->formatBookingTime($date);

        return "{$formattedDate} @ {$formattedTime}";
    }

    public function toSMS(VenueContactData $notifiable): SmsData
    {
        // Handle different templates and formatting for hike stations
        if ($this->booking->venue->venue_type == VenueType::HIKE_STATION) {
            if ($this->reminder) {
                $templateKey = filled($this->booking->notes)
                    ? 'venue_contact_booking_confirmed_reminder_notes_hike'
                    : 'venue_contact_booking_confirmed_reminder_hike';
            } else {
                $templateKey = filled($this->booking->notes)
                    ? 'venue_contact_booking_confirmed_notes_hike'
                    : 'venue_contact_booking_confirmed_hike';
            }

            $templateData = [
                'venue_name' => $this->booking->venue->name,
                'booking_date_time' => $this->formatBookingDateTime($this->booking->booking_at),
                'guest_name' => $this->booking->guest_name,
                'guest_count' => $this->booking->guest_count,
                'guest_phone' => $this->booking->guest_phone,
                'confirmation_url' => $this->confirmationUrl,
            ];
        } else {
            $baseTemplate = $this->reminder
                ? 'venue_contact_booking_confirmed_reminder'
                : 'venue_contact_booking_confirmed';

            $templateKey = filled($this->booking->notes)
                ? $baseTemplate.'_notes'
                : $baseTemplate;

            $templateData = [
                'venue_name' => $this->booking->venue->name,
                'booking_date' => $this->formatBookingDate($this->booking->booking_at),
                'booking_time' => $this->formatBookingTime($this->booking->booking_at),
                'guest_name' => $this->booking->guest_name,
                'guest_count' => $this->booking->guest_count,
                'guest_phone' => $this->booking->guest_phone,
                'confirmation_url' => $this->confirmationUrl,
            ];
        }

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
        // Handle different formatting for hike stations
        if ($this->booking->venue->venue_type == VenueType::HIKE_STATION) {
            $bookingDateTime = $this->formatBookingDateTime($this->booking->booking_at);
            $bookingTimeDisplay = $bookingDateTime; // For email, show the combined format
        } else {
            $bookingDate = $this->formatBookingDate($this->booking->booking_at);
            $bookingTime = $this->formatBookingTime($this->booking->booking_at);
            $bookingTimeDisplay = "{$bookingDate} @ {$bookingTime}";
        }

        $notes = $this->booking->notes ?? 'NA';

        if ($this->reminder) {
            $subject = "Reminder: Upcoming Booking at {$this->booking->venue->name}";
            $greeting = 'Booking Reminder';
            $introLine = "This is a reminder for an upcoming booking at {$this->booking->venue->name}.";
        } else {
            $subject = "New Booking Confirmation - {$this->booking->venue->name}";
            $greeting = 'New Booking Confirmation';
            $introLine = "A new booking has been confirmed at {$this->booking->venue->name}.";
        }

        return (new MailMessage)
            ->from('prima@primavip.co', 'PRIMA Reservation Platform')
            ->subject($subject)
            ->greeting($greeting)
            ->line($introLine)
            ->line('**Booking Details:**')
            ->line("When: {$bookingTimeDisplay}")
            ->line("Party Size: {$this->booking->guest_count}")
            ->line("Customer: {$this->booking->guest_name}")
            ->line("Customer phone: {$this->booking->guest_phone}")
            ->line("Notes: {$notes}")
            ->line('**Confirmation Link:**')
            ->action('Confirm', $this->confirmationUrl);
    }

    /**
     * Determine if the notification should be sent.
     */
    public function shouldSend(): bool
    {
        return $this->booking->is_confirmed;
    }
}

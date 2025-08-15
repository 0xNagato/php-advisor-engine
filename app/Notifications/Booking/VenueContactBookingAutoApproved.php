<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\Models\Booking;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VenueContactBookingAutoApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  VenueContactData  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        if ($notifiable instanceof VenueContactData) {
            return $notifiable->toChannel();
        }

        // For admin notifications (email only)
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $venue = $this->booking->venue;
        $bookingTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->booking->booking_at,
            $venue->timezone
        );

        $subject = $notifiable instanceof VenueContactData
            ? "PRIMA Auto-Approved Booking - {$venue->name}"
            : 'PRIMA Auto-Approved Booking - Admin Notification';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello from PRIMA!')
            ->line('A booking has been automatically approved and added to your system:')
            ->line("**Restaurant:** {$venue->name}")
            ->line("**Date:** {$bookingTime->format('l, F j, Y')}")
            ->line("**Time:** {$bookingTime->format('g:i A')}")
            ->line("**Party Size:** {$this->booking->guest_count} guests")
            ->line("**Guest Name:** {$this->booking->guest_name}")
            ->line("**Guest Phone:** {$this->booking->guest_phone}")
            ->when($this->booking->notes, fn ($mail) => $mail->line("**Special Notes:** {$this->booking->notes}"))
            ->line('')
            ->line('This reservation was automatically approved because:')
            ->line('• Party size is 7 guests or under')
            ->line('• Your restaurant uses an integrated booking platform')
            ->line('• The reservation was successfully added to your platform')
            ->line('')
            ->line('No further action is required - the booking is already confirmed in your system.')
            ->salutation('Thank you for partnering with PRIMA!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(VenueContactData $notifiable): SmsData
    {
        $venue = $this->booking->venue;
        $bookingTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->booking->booking_at,
            $venue->timezone
        );

        $templateKey = $this->booking->notes
            ? 'venue_contact_booking_auto_approved_notes'
            : 'venue_contact_booking_auto_approved';

        $url = route('venues.confirm', ['booking' => $this->booking]);
        $confirmationUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        $templateData = [
            'platform_name' => $this->getPlatformName($venue),
            'venue_name' => $venue->name,
            'booking_date' => $bookingTime->format('M jS'),
            'booking_time' => $bookingTime->format('g:i A'),
            'guest_count' => $this->booking->guest_count,
            'guest_name' => $this->booking->guest_name,
            'guest_phone' => $this->booking->guest_phone,
            'confirmation_url' => $confirmationUrl,
        ];

        if ($this->booking->notes) {
            $templateData['notes'] = $this->booking->notes;
        }

        return new SmsData(
            phone: $notifiable->contact_phone,
            templateKey: $templateKey,
            templateData: $templateData
        );
    }

    /**
     * Get the platform name for the venue.
     */
    private function getPlatformName($venue): string
    {
        $enabledPlatforms = $venue->platforms()->where('is_enabled', true)->get();

        foreach ($enabledPlatforms as $platform) {
            return match ($platform->platform_type) {
                'covermanager' => 'CoverManager',
                'restoo' => 'Restoo',
                default => ucfirst((string) $platform->platform_type),
            };
        }

        // Fallback if no enabled platforms found
        return 'your booking platform';
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'venue_id' => $this->booking->venue->id,
            'venue_name' => $this->booking->venue->name,
            'guest_name' => $this->booking->guest_name,
            'guest_count' => $this->booking->guest_count,
            'booking_at' => $this->booking->booking_at,
            'auto_approved' => true,
        ];
    }
}

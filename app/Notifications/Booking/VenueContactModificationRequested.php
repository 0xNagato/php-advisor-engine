<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\Models\BookingModificationRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VenueContactModificationRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VenueContactData $contact,
        public string $confirmationUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return [...$this->contact->toChannel(), 'mail'];
    }

    public function toSMS(BookingModificationRequest $notifiable): SmsData
    {
        $booking = $notifiable->booking;

        return new SmsData(
            phone: $this->contact->contact_phone,
            templateKey: 'venue_modification_request',
            templateData: [
                'venue_name' => $booking->venue->name,
                'guest_name' => $booking->guest_name,
                'guest_phone' => $booking->guest_phone,
                'booking_date' => Carbon::toNotificationFormat($booking->booking_at),
                'booking_time' => $booking->booking_at->format('g:ia'),
                'changes_requested' => $this->getChangesDescription($notifiable),
                'confirmation_url' => $this->confirmationUrl,
            ]
        );
    }

    public function toMail(BookingModificationRequest $notifiable): MailMessage
    {
        $booking = $notifiable->booking;

        return (new MailMessage)
            ->subject('Booking Modification Request - '.$booking->venue->name)
            ->greeting('Booking Modification Request')
            ->line("A modification request has been received for the booking at {$booking->venue->name}.")
            ->line('Booking Details:')
            ->line("Guest: {$booking->guest_name}")
            ->line("Phone: {$booking->guest_phone}")
            ->line('Date: '.Carbon::toNotificationFormat($booking->booking_at))
            ->line('Time: '.$booking->booking_at->format('g:ia'))
            ->line('Requested Changes:')
            ->line($this->getChangesDescription($notifiable))
            ->action('Review Modification Request', $this->confirmationUrl)
            ->line('Please review and confirm the requested changes.');
    }

    private function getChangesDescription(BookingModificationRequest $request): string
    {
        $changes = [];

        if ($request->original_guest_count !== $request->requested_guest_count) {
            $changes[] = "Party size: {$request->requested_guest_count}";
        }

        if ($request->original_time !== $request->requested_time) {
            $changes[] = 'Time: '.date('g:ia', strtotime($request->requested_time));
        }

        return implode(', ', $changes);
    }
}

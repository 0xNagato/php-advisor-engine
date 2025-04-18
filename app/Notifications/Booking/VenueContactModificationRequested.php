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
        public BookingModificationRequest $modificationRequest,
        public string $confirmationUrl,
    ) {}

    public function via(VenueContactData $notifiable): array
    {
        return $notifiable->toChannel();
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

    public function toSMS(VenueContactData $notifiable): SmsData
    {
        $booking = $this->modificationRequest->booking;

        return new SmsData(
            phone: $notifiable->contact_phone,
            templateKey: 'venue_modification_request',
            templateData: [
                'venue_name' => $booking->venue->name,
                'guest_name' => $booking->guest_name,
                'guest_phone' => $booking->guest_phone,
                'booking_date' => Carbon::toNotificationFormat($booking->booking_at),
                'booking_time' => $booking->booking_at->format('g:ia'),
                'changes_requested' => $this->getChangesDescription($this->modificationRequest),
                'confirmation_url' => $this->confirmationUrl,
            ]
        );
    }

    public function toMail(): MailMessage
    {
        $booking = $this->modificationRequest->booking;
        $bookingDate = Carbon::toNotificationFormat($booking->booking_at);
        $bookingTime = $booking->booking_at->format('g:ia');

        return (new MailMessage)
            ->from('prima@primavip.co', 'PRIMA Reservation Platform')
            ->subject("PRIMA Notice: Change Request - {$booking->venue->name}")
            ->greeting('Booking change requested.')
            ->line('A change has been requested.')
            ->line('**Booking Details:**')
            ->line("Customer: {$booking->guest_name}")
            ->line("Customer phone: {$booking->guest_phone}")
            ->line("Date: {$bookingDate}")
            ->line("Time: {$bookingTime}")
            ->line('Change requested:')
            ->line($this->getChangesDescription($this->modificationRequest))
            ->line('**Confirmation Link:**')
            ->action('Confirm', $this->confirmationUrl);
    }

    public function shouldSendNow(): bool
    {
        return $this->modificationRequest->booking->is_confirmed;
    }
}

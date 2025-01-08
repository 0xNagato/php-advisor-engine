<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Data\VenueContactData;
use App\Models\BookingModificationRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        return $this->contact->toChannel();
    }

    public function toSMS(BookingModificationRequest $notifiable): SmsData
    {
        $booking = $notifiable->booking;

        return new SmsData(
            phone: $this->contact->contact_phone,
            templateKey: 'venue_modification_request',
            templateData: [
                'guest_name' => $booking->guest_name,
                'guest_phone' => $booking->guest_phone,
                'booking_date' => Carbon::toNotificationFormat($booking->booking_at),
                'booking_time' => $booking->booking_at->format('g:ia'),
                'changes_requested' => $this->getChangesDescription($notifiable),
                'confirmation_url' => $this->confirmationUrl,
            ]
        );
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

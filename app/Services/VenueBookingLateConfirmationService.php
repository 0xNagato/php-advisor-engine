<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class VenueBookingLateConfirmationService
{
    public function sendNotification(Booking $booking): void
    {
        User::role('super_admin')->each(function ($admin) use ($booking) {
            $this->sendSMS($booking, $admin);
        });
    }

    public function sendSMS(Booking $booking, User $contact): void
    {
        $name = $booking->venue->name;
        $bookingDate = Carbon::toNotificationFormat($booking->booking_at);
        $bookingTime = $booking->booking_at->format('g:ia');

        app(SmsService::class)->sendMessage(
            $contact->phone,
            "PRIMA Reservation - Venue $name failed to confirm the reservation #$booking->id scheduled for $bookingDate, at $bookingTime"
        );
    }
}

<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class RestaurantBookingLateConfirmationService
{
    public function sendNotification(Booking $booking): void
    {
        User::role('super_admin')->each(function ($admin) use ($booking) {
            $this->sendSMS($booking, $admin);
        });
    }

    public function sendSMS(Booking $booking, User $contact): void
    {
        $restaurant_name = $booking->restaurant->restaurant_name;
        $bookingDate = Carbon::toNotificationFormat($booking->booking_at);
        $bookingTime = $booking->booking_at->format('g:ia');

        app(SmsService::class)->sendMessage(
            $contact->phone,
            "PRIMA Reservation - Restaurant $restaurant_name failed to confirm the reservation #$booking->id scheduled for $bookingDate, at $bookingTime"
        );
    }
}

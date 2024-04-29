<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Carbon\CarbonInterface;

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
        $bookingDate = $this->getFormattedDate($booking->booking_at);
        $bookingTime = $booking->booking_at->format('g:ia');

        app(SmsService::class)->sendMessage(
            $contact->phone,
            "PRIMA Reservation - Restaurant $restaurant_name failed to confirm the reservation #$booking->id scheduled for $bookingDate, at $bookingTime"
        );
    }

    private function getFormattedDate(CarbonInterface $date): string
    {
        $today = now();
        $tomorrow = now()->addDay();

        if ($date->isSameDay($today)) {
            return 'today';
        }

        if ($date->isSameDay($tomorrow)) {
            return 'tomorrow';
        }

        return $date->format('l \\t\\h\\e jS');
    }
}

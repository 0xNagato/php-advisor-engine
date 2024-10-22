<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\VenueFailedToConfirmBooking;
use Lorisleiva\Actions\Concerns\AsAction;

class NotifyAdminsOfUnconfirmedBooking
{
    use AsAction;

    public function handle(Booking $booking): void
    {
        User::role('super_admin')->each(function ($admin) use ($booking) {
            $admin->notify(new VenueFailedToConfirmBooking($booking));
        });
    }
}

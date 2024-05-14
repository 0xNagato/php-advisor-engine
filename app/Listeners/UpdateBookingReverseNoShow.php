<?php

namespace App\Listeners;

use App\Events\BookingReverseMarkedAsNoShow;
use App\Models\Booking;

class UpdateBookingReverseNoShow
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BookingReverseMarkedAsNoShow $event): void
    {
        Booking::calculateNonPrimeEarnings($event->booking, true);
    }
}

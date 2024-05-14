<?php

namespace App\Listeners;

use App\Events\BookingMarkedAsNoShow;
use App\Models\Booking;

class UpdateBookingNoShow
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
    public function handle(BookingMarkedAsNoShow $event): void
    {
        Booking::reverseNonPrimeEarnings($event->booking);
    }
}

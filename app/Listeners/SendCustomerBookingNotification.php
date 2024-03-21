<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCustomerBookingNotification implements ShouldQueue
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
    public function handle(BookingPaid $event): void
    {
        //
    }
}

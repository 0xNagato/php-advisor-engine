<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use App\Services\BookingConfirmationService;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendRestaurantBookingConfirmation implements ShouldQueue
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
     *
     * @throws ShortURLException
     */
    public function handle(BookingPaid $event): void
    {
        $service = new BookingConfirmationService();
        $service->sendConfirmation($event->booking);
    }
}

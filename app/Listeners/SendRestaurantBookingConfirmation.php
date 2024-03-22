<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use App\Services\RestaurantContactBookingConfirmationService;
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
        app(RestaurantContactBookingConfirmationService::class)->sendConfirmation($event->booking);
    }
}

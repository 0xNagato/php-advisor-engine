<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use App\Notifications\CustomerBookingPaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use NotificationChannels\Twilio\TwilioChannel;

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
        $notifiable = (new AnonymousNotifiable())->route(TwilioChannel::class, $event->booking->guest_phone);
        $notifiable->notify(new CustomerBookingPaid($event->booking));
    }
}

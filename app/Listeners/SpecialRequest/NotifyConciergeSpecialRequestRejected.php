<?php

namespace App\Listeners\SpecialRequest;

use App\Events\SpecialRequestRejected;
use App\Filament\Pages\Concierge\SpecialRequests;
use App\Services\SmsService;

class NotifyConciergeSpecialRequestRejected
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
    public function handle(SpecialRequestRejected $event): void
    {
        $concierge = $event->specialRequest->concierge;
        $restaurant = $event->specialRequest->restaurant;
        $link = SpecialRequests::getUrl();

        $message = "Special request has been rejected from $restaurant->restaurant_name. Click here for more details $link.";

        app(SmsService::class)->sendMessage($concierge->user->phone, $message);
    }
}

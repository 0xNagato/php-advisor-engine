<?php

namespace App\Listeners\SpecialRequest;

use App\Events\SpecialRequestChangesRequested;
use App\Filament\Pages\Concierge\SpecialRequests;
use App\Services\SmsService;

class NotifyConciergeSpecialRequestChangesRequested
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
    public function handle(SpecialRequestChangesRequested $event): void
    {
        $concierge = $event->specialRequest->concierge;
        $restaurant = $event->specialRequest->restaurant;
        $link = SpecialRequests::getUrl();

        $message = "Special request changes have been requested from $restaurant->restaurant_name. Click here for more details $link.";

        app(SmsService::class)->sendMessage($concierge->user->phone, $message);
    }
}

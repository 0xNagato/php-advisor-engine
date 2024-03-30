<?php

namespace App\Listeners;

use App\Events\ConciergeReferredViaText;
use App\Notifications\ConciergeReferredText;

class SendConciergeReferralTextInvitation
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
    public function handle(ConciergeReferredViaText $event): void
    {
        $event->conciergeReferral->notify(new ConciergeReferredText($event->conciergeReferral));
    }
}

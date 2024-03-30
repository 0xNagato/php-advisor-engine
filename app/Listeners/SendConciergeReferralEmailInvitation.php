<?php

namespace App\Listeners;

use App\Events\ConciergeReferredViaEmail;
use App\Notifications\ConciergeReferredEmail;

class SendConciergeReferralEmailInvitation
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
    public function handle(ConciergeReferredViaEmail $event): void
    {
        $event->conciergeReferral->notify(new ConciergeReferredEmail($event->conciergeReferral));
    }
}

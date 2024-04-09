<?php

namespace App\Listeners;

use App\Events\ConciergeReferredViaEmail;
use App\Notifications\ConciergeReferredEmail;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;

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
     * @throws ShortURLException
     */
    public function handle(ConciergeReferredViaEmail $event): void
    {
        $event->referral->notify(new ConciergeReferredEmail($event->referral));
    }
}

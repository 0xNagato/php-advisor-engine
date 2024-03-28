<?php

namespace App\Listeners;

use App\Events\ConciergeReferred;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;

class SendConciergeReferralInvitation
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
    public function handle(ConciergeReferred $event): void
    {
        info('Sending referral invitation', ['user_id' => $event->user->id]);
        $event->user->notify(new \App\Notifications\ConciergeReferred($event->user));
    }
}

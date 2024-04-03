<?php

namespace App\Listeners;

use App\Events\ConciergeReferredViaText;
use App\Services\SimpleTextingAdapter;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Support\Facades\URL;

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
        $referrer = $event->conciergeReferral->concierge->user;

        $url = URL::temporarySignedRoute('concierge.invitation', now()->addDays(), [
            'conciergeReferral' => $event->conciergeReferral,
        ]);

        $shortURL = ShortURL::destinationUrl($url)->make()->default_short_url;

        app(SimpleTextingAdapter::class)->sendMessage(
            $event->conciergeReferral->phone,
            "You've been invited to PRIMA by $referrer->name. Please click $shortURL to create your profile and start earning! Welcome aboard!"
        );

    }
}

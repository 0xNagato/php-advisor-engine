<?php

namespace App\Listeners;

use App\Events\ConciergeReferredViaText;
use App\Services\SimpleTextingAdapter;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\URL;
use JsonException;

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
     * @throws ShortURLException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function handle(ConciergeReferredViaText $event): void
    {
        $referrer = $event->referral->referrer;

        $url = URL::temporarySignedRoute('concierge.invitation', now()->addDays(), [
            'referral' => $event->referral,
        ]);

        $shortURL = ShortURL::destinationUrl($url)->make()->default_short_url;


        app(SimpleTextingAdapter::class)->sendMessage(
            $event->referral->phone,
            "You've been invited to PRIMA by $referrer->name. Please click $shortURL to create your profile and start earning! Welcome aboard!"
        );
    }
}

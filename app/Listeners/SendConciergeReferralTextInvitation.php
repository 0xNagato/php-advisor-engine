<?php

namespace App\Listeners;

use App\Events\ConciergeReferredViaText;
use App\Services\SmsService;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
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
     *
     * @throws ShortURLException
     */
    public function handle(ConciergeReferredViaText $event): void
    {
        $referrer = $event->referral->referrer;

        $url = URL::temporarySignedRoute('concierge.invitation', now()->addDays(15), [
            'referral' => $event->referral,
        ]);

        $shortURL = ShortURL::destinationUrl($url)->make()->default_short_url;

        app(SmsService::class)->sendMessage(
            $event->referral->phone,
            "Hi {$event->referral->first_name}! You've been invited to join PRIMA VIP by $referrer->name. Please click $shortURL to set up your account now and welcome to the team!  We look forward to working with you!"
        );
    }
}

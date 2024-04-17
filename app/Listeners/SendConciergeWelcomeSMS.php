<?php

namespace App\Listeners;

use App\Events\ConciergeCreated;
use App\Services\SmsService;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Password;
use ShortURL;

class SendConciergeWelcomeSMS
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
    public function handle(ConciergeCreated $event): void
    {
        $token = Password::createToken($event->concierge->user);
        $url = Filament::getResetPasswordUrl($token, $event->concierge->user);

        $secureUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        app(SmsService::class)->sendMessage(
            $event->concierge->user->phone,
            "Welcome to PRIMA! Your account has been created. Please click $secureUrl to login and update your payment info and begin making reservations. Thank you for joining us!"
        );
    }
}

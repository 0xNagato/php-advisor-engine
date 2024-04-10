<?php

namespace App\Listeners;

use App\Events\ConciergeCreated;
use App\Services\SimpleTextingAdapter;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Filament\Facades\Filament;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Password;
use JsonException;
use Sentry;
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

        try {
            app(SimpleTextingAdapter::class)->sendMessage(
                $event->concierge->user->phone,
                "Welcome to PRIMA! Your account has been created. Please click $secureUrl to login and update your payment info and begin making reservations. Thank you for joining us!"
            );
        } catch (GuzzleException|JsonException $exception) {
            info('Failed to send SMS');
            Sentry::captureException($exception);
        }

    }
}

<?php

namespace App\Listeners;

use App\Events\RestaurantCreated;
use App\Services\SimpleTextingAdapter;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Filament\Facades\Filament;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Password;
use JsonException;
use Sentry;
use ShortURL;

class SendRestaurantWelcomeSMS
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
    public function handle(RestaurantCreated $event): void
    {
        $token = Password::createToken($event->restaurant->user);
        $url = Filament::getResetPasswordUrl($token, $event->restaurant->user);

        $secureUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        try {
            app(SimpleTextingAdapter::class)->sendMessage(
                $event->restaurant->user->phone,
                "Welcome to PRIMA! Your account has been created. Please click $secureUrl to login and update your payment info and begin making reservations. Thank you for joining us!"
            );
        } catch (GuzzleException|JsonException $exception) {
            info('Failed to send SMS');
            Sentry::captureException($exception);
        }

    }
}

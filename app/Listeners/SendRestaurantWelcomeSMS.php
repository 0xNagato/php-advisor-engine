<?php

namespace App\Listeners;

use App\Events\RestaurantInvited;
use App\Services\SmsService;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Filament\Facades\Filament;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Password;
use JsonException;
use Sentry;
use ShortURL;

class SendRestaurantWelcomeSMS implements ShouldQueue
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
    public function handle(RestaurantInvited $event): void
    {
        $token = Password::createToken($event->restaurant->user);
        $url = Filament::getResetPasswordUrl($token, $event->restaurant->user);

        $secureUrl = ShortURL::destinationUrl($url)->make()->default_short_url;

        try {
            app(SmsService::class)->sendMessage(
                $event->restaurant->user->phone,
                // "Welcome to PRIMA! Your account has been created. Please click $secureUrl to login and update your payment info and begin making reservations. Thank you for joining us!"
                '❤️ Thank you for joining PRIMA! Our concierge team is currently being onboarded and will start generating reservations soon! We will notify you via text as soon as we are ready to launch! With gratitude, Team PRIMA.'
            );
        } catch (GuzzleException|JsonException $exception) {
            info('Failed to send SMS');
            Sentry::captureException($exception);
        }

    }
}

<?php

namespace App\Http\Integrations\SimpleTexting;

use App\Http\Integrations\SimpleTexting\Requests\SendMessage;
use Log;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

class SimpleTexting extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return config('services.simple_texting.base_uri');
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator(
            token: config('services.simple_texting.api_key')
        );
    }

    public function sms($phone, $text): Response
    {
        Log::info('Sending SMS to '.$phone, [
            'text' => $text,
            'provider' => 'SIMPLE_TEXTING',
        ]);

        return $this->send(new SendMessage($phone, $text));
    }
}

<?php

namespace App\Http\Integrations\ClickSend;

use App\Http\Integrations\ClickSend\Requests\SendMessage;
use Log;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

class ClickSend extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        // ClickSend API base URL v3
        return 'https://rest.clicksend.com/v3';
    }

    protected function defaultAuth(): BasicAuthenticator
    {
        return new BasicAuthenticator(
            username: config('services.clicksend.username'),
            password: config('services.clicksend.api_key')
        );
    }

    /**
     * Send an SMS message via ClickSend.
     *
     * @param  string  $phone  The recipient phone number.
     * @param  string  $text  The message text.
     * @param  string|null  $from  The sender ID or number to send from. Defaults to config if null.
     */
    public function sms(string $phone, string $text, ?string $from = null): Response
    {
        Log::info('Sending SMS to '.$phone, [
            'text' => $text,
            'provider' => 'CLICKSEND',
            'from' => $from ?? config('services.clicksend.from'), // Log the effective 'from'
        ]);

        // Pass the specific 'from' number to the SendMessage request
        return $this->send(new SendMessage($phone, $text, $from));
    }
}

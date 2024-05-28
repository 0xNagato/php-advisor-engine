<?php

namespace App\Http\Integrations\Twilio;

use App\Http\Integrations\Twilio\Requests\SendMessage;
use Exception;
use Illuminate\Support\Str;
use Log;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

class Twilio extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.twilio.com/2010-04-01/Accounts/'.config('services.twilio.sid');
    }

    protected function defaultAuth(): BasicAuthenticator
    {
        return new BasicAuthenticator(
            username: config('services.twilio.sid'),
            password: config('services.twilio.token')
        );
    }

    public function sms(string $phone, string $text): Response
    {
        Log::info('Sending SMS to '.$phone, [
            'text' => $text,
            'provider' => 'TWILIO',
        ]);

        return $this->send(new SendMessage($phone, $text));
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws Exception
     */
    public function whatsapp(string $phone, string $text): Response
    {
        if (Str::startsWith($phone, 'whatsapp:')) {
            throw new Exception('Phone Number should not start with whatsapp:');
        }

        Log::info('Sending WhatsApp message to '.$phone, [
            'text' => $text,
            'provider' => 'TWILIO',
        ]);

        return $this->send(new SendMessage(
            phone: 'whatsapp:'.$phone,
            text: $text,
            from: config('services.twilio.from_whatsapp')
        ));
    }
}

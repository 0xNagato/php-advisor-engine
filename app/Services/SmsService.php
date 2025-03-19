<?php

namespace App\Services;

use App\Http\Integrations\Twilio\Twilio;
use App\Jobs\SendSimpleTextingSmsJob;
use Saloon\Http\Response;

class SmsService
{
    public function sendMessage(
        string $contactPhone,
        string $text,
    ): ?Response {
        $phoneNumber = PhoneNumberParser::make($contactPhone)->parse();

        if ($phoneNumber->country === 'US') {
            SendSimpleTextingSmsJob::dispatch(
                phone: $phoneNumber->phone,
                text: $text
            );

            return null;
        }

        return (new Twilio)->sms(
            phone: $phoneNumber->phone,
            text: $text
        );
    }
}

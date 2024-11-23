<?php

namespace App\Services;

use App\Http\Integrations\SimpleTexting\SimpleTexting;
use App\Http\Integrations\Twilio\Twilio;
use Saloon\Http\Response;

class SmsService
{
    public function sendMessage(
        string $contactPhone,
        string $text,
    ): ?Response {
        $phoneNumber = PhoneNumberParser::make($contactPhone)->parse();

        if ($phoneNumber->country === 'US') {
            return (new SimpleTexting)->sms(
                phone: $phoneNumber->phone,
                text: $text,
            );
        }

        return (new Twilio)->sms(
            phone: $phoneNumber->phone,
            text: $text
        );
    }
}

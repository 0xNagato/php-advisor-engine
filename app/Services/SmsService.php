<?php

namespace App\Services;

use App\Http\Integrations\SimpleTexting\SimpleTexting;
use App\Http\Integrations\Twilio\Twilio;
use Illuminate\Support\Facades\Log;
use Saloon\Http\Response;

class SmsService
{
    public function sendMessage(
        string $contactPhone,
        string $text,
    ): ?Response {
        if (app()->isLocal()) {
            Log::info('Sending SMS', [
                'phone' => $contactPhone,
                'text' => $text,
            ]);

            return null;
        }

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

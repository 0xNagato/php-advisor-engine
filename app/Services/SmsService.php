<?php

namespace App\Services;

use App\Http\Integrations\ClickSend\ClickSend;
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
        } elseif (in_array($phoneNumber->country, config('services.clicksend.countries', ['GB']))) {
            // Look up the country-specific 'from' number
            $fromNumber = config('services.clicksend.from_numbers.GB');

            return (new ClickSend)->sms(
                phone: $phoneNumber->phone,
                text: $text,
                from: $fromNumber // Pass the specific UK 'from' number
            );
        }

        // Fallback to Twilio for other international numbers
        return (new Twilio)->sms(
            phone: $phoneNumber->phone,
            text: $text
        );
    }
}

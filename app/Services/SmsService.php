<?php

namespace App\Services;

use App\Traits\FormatsPhoneNumber;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Log\Logger;
use JsonException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Sentry;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class SmsService
{
    use FormatsPhoneNumber;

    protected SimpleTextingAdapter $simpleTextingAdapter;

    protected Client $twilioClient;

    public function __construct(SimpleTextingAdapter $simpleTextingAdapter)
    {
        $this->simpleTextingAdapter = $simpleTextingAdapter;
    }

    public function sendMessage(
        $contactPhone,
        $text,
        $accountPhone = null,
        $mode = 'AUTO',
        $subject = null,
        $fallbackText = null,
        $mediaItems = [],
    )
    {
        $contactPhone = $this->getInternationalFormattedPhoneNumber($contactPhone);
        $phoneUtil = PhoneNumberUtil::getInstance();
        app(Logger::class)->info('Parsing phone number ' . $contactPhone);
        try {
            $phoneNumber = $phoneUtil->parse($contactPhone);
            app(Logger::class)->info('Parsing phone number ' . $contactPhone);
            $countryCode = $phoneUtil->getRegionCodeForNumber($phoneNumber);


            if ($countryCode === 'US' || $countryCode === 'CA') {
                app(Logger::class)->info('Sending SMS to ' . $contactPhone, [
                    'countryCode' => $countryCode,
                    'text' => $text,
                    'provider' => 'SIMPLE_TEXTING',
                ]);

                return $this->simpleTextingAdapter->sendMessage(
                    $contactPhone,
                    $text,
                    $accountPhone,
                    $mode,
                    $subject,
                    $fallbackText,
                    $mediaItems
                );
            }

            $twilioClient = new Client(
                config('twilio-notification-channel.sid'),
                config('twilio-notification-channel.token')
            );

            app(Logger::class)->info('Sending SMS to ' . $contactPhone, [
                'countryCode' => $countryCode,
                'text' => $text,
                'provider' => 'TWILIO',
            ]);

            return $twilioClient->messages->create(
                $contactPhone,
                [
                    'from' => $accountPhone ?? config('twilio-notification-channel.from'),
                    'body' => $text,
                ]
            );
        } catch (NumberParseException|GuzzleException|JsonException|TwilioException $exception) {
            app(Logger::class)->error($exception->getMessage());
            Sentry::captureException($exception);
        }

        return false;
    }
}

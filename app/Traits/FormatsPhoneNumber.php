<?php

namespace App\Traits;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Sentry;

trait FormatsPhoneNumber
{
    public function getLocalFormattedPhoneNumber($value): string
    {
        return $this->getFormattedPhoneNumber($value);
    }

    public function getFormattedPhoneNumber($value, $format = PhoneNumberFormat::NATIONAL): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {

            if ($value === null) {
                return '';
            }
            $numberProto = $phoneUtil->parse($value, 'US');

            // Check if the number is valid
            if ($phoneUtil->isValidNumber($numberProto)) {
                // Format the number in the specified format
                return $phoneUtil->format($numberProto, $format);
            }

            $repairedNumber = '+1'.$value;
            $numberProto = $phoneUtil->parse($repairedNumber, 'US');

            if ($phoneUtil->isValidNumber($numberProto)) {
                return $phoneUtil->format($numberProto, $format);
            }
        } catch (NumberParseException $e) {
            Sentry::captureException($e);

            return $value;
        }

        return $value;
    }

    public function getInternationalFormattedPhoneNumber($value): string
    {
        return $this->getFormattedPhoneNumber($value, PhoneNumberFormat::E164);
    }
}

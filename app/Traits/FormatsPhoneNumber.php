<?php

namespace App\Traits;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

trait FormatsPhoneNumber
{
    public function getLocalFormattedPhoneNumber($value): string
    {
        return $this->getFormattedPhoneNumber($value, PhoneNumberFormat::NATIONAL);
    }

    public function getFormattedPhoneNumber($value, $format = PhoneNumberFormat::NATIONAL): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
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
            return $value;
        }

        return $value;
    }

    public function getInternationalFormattedPhoneNumber($value): string
    {
        return $this->getFormattedPhoneNumber($value, PhoneNumberFormat::E164);
    }
}

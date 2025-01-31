<?php

namespace App\Services;

use App\Data\PhoneNumberData;
use App\Traits\FormatsPhoneNumber;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Log;
use Sentry;

class PhoneNumberParser
{
    use FormatsPhoneNumber;

    public function __construct(
        protected string $phone,
    ) {}

    public static function make(string $phone): self
    {
        return new self($phone);
    }

    public function parse(): PhoneNumberData
    {
        $contactPhone = $this->getInternationalFormattedPhoneNumber($this->phone);
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($contactPhone);
            $countryCode = $phoneUtil->getRegionCodeForNumber($phoneNumber);

            return new PhoneNumberData(
                phone: $contactPhone,
                country: $countryCode ?? 'Unknown',
            );
        } catch (NumberParseException $exception) {
            Log::error($exception->getMessage());
            Sentry::captureException($exception);
        }

        return new PhoneNumberData(
            phone: $contactPhone,
            country: 'Unknown',
        );
    }
}

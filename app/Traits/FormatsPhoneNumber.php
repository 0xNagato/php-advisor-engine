<?php

namespace App\Traits;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

trait FormatsPhoneNumber
{
    public function getLocalFormattedPhoneNumber($value): string
    {
        return $this->getFormattedPhoneNumber($value);
    }

    public function getFormattedPhoneNumber($value, $format = PhoneNumberFormat::NATIONAL): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        if ($value === null || trim($value) === '') {
            return '';
        }

        // Try various parsing strategies
        $possibleRegions = ['US', null];
        $validNumberFound = false;
        $parsedProto = null;

        foreach ($possibleRegions as $region) {
            try {
                // First, try parsing as-is
                $numberProto = $phoneUtil->parse($value, $region);

                // If valid, store it
                if ($phoneUtil->isValidNumber($numberProto)) {
                    $validNumberFound = true;
                    $parsedProto = $numberProto;
                    break;
                }
            } catch (NumberParseException) {
                // Parsing failed, continue with next strategy
            }

            // If the number doesn't start with +, try adding different prefixes
            if (! str_starts_with(trim($value), '+')) {
                $prefixes = ['', '+', '+1'];

                foreach ($prefixes as $prefix) {
                    try {
                        $formattedInput = $prefix.preg_replace('/^\+/', '', $value);
                        $numberProto = $phoneUtil->parse($formattedInput, $region);

                        if ($phoneUtil->isValidNumber($numberProto)) {
                            $validNumberFound = true;
                            $parsedProto = $numberProto;
                            break 2; // Break out of both loops
                        }
                    } catch (NumberParseException) {
                        // Continue to next prefix
                    }
                }
            }
        }

        // Only return a formatted number if we found a valid one
        if ($validNumberFound && $parsedProto) {
            return $phoneUtil->format($parsedProto, $format);
        }

        // Return empty string to indicate validation failed
        // This will force the frontend to handle the invalid phone number
        return '';
    }

    public function getInternationalFormattedPhoneNumber($value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $value = preg_replace('/\s+/', '', (string) $value);

        $formatted = $this->getFormattedPhoneNumber($value, PhoneNumberFormat::E164);

        // If formatting failed (returns empty string), return empty to indicate failure
        if (blank($formatted)) {
            return '';
        }

        // Ensure it has a + prefix for E164 format
        return str_starts_with((string) $formatted, '+') ? $formatted : '+'.$formatted;
    }
}

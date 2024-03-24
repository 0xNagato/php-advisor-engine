<?php

if (! function_exists('formatPhoneNumber')) {
    function formatPhoneNumber($phoneNumber)
    {
        // Remove any non-digit character
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        // If the number starts with '1' (country code), remove it
        if (substr($phoneNumber, 0, 1) === '1') {
            $phoneNumber = substr($phoneNumber, 1);
        }

        // If the number has 10 digits, format it
        if (strlen($phoneNumber) === 10) {
            $areaCode = substr($phoneNumber, 0, 3);
            $prefix = substr($phoneNumber, 3, 3);
            $lineNumber = substr($phoneNumber, 6, 4);

            $formattedPhoneNumber = '('.$areaCode.') '.$prefix.'-'.$lineNumber;

            return $formattedPhoneNumber;
        }

        // If the number doesn't have 10 digits, return it as is
        return $phoneNumber;
    }
}

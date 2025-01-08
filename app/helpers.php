<?php

use App\Traits\FormatsPhoneNumber;

if (! function_exists('formatInternationalPhoneNumber')) {
    function formatInternationalPhoneNumber($phoneNumber): string
    {
        return (new class
        {
            use FormatsPhoneNumber;
        })->getInternationalFormattedPhoneNumber($phoneNumber);
    }
}

if (! function_exists('isPrimaApp')) {
    function isPrimaApp(): bool
    {
        $request = request();

        return $request->userAgent() === 'PrimaApp/1.0';
    }
}

if (! function_exists('isAndroid')) {
    function isAndroid(): bool
    {
        return str_contains(request()->header('sec-ch-ua-platform') ?? '', 'Android');
    }
}

if (! function_exists('moneyWithoutCents')) {
    function moneyWithoutCents($amount, $currency): string
    {
        $formatted = money($amount, $currency);

        // Remove any cents value from the end of the string
        return preg_replace('/\.\d{2}$/', '', $formatted);
    }
}

if (! function_exists('formatRoleName')) {
    function formatRoleName(string $name): string
    {
        return Str::title(str_replace('_', ' ', $name));
    }
}

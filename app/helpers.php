<?php

use App\Models\Region;
use App\Traits\FormatsPhoneNumber;
use Carbon\Carbon;

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

if (! function_exists('isPastCutoffTime')) {
    function isPastCutoffTime($venue, $timezone = null): bool
    {
        if (! $venue->cutoff_time) {
            return false;
        }

        $timezone ??= Region::query()->find($venue->region)->timezone;
        $now = now()->setTimezone($timezone);
        $cutoffTime = Carbon::parse($venue->cutoff_time)->setTimezone($timezone);

        return $now->format('H:i:s') > $cutoffTime->format('H:i:s');
    }
}

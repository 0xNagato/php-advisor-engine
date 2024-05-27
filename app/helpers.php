<?php

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

if (! function_exists('formatDateFromString')) {
    function formatDateFromString($date): string
    {
        if (auth()->check()) {
            $user = auth()->user();
            $timezone = $user->timezone;
        } else {
            $timezone = config('app.timezone');
        }

        $carbonDate = Carbon::createFromFormat('Y-m-d', $date, $timezone);
        if ($carbonDate?->isToday()) {
            return 'Today';
        }

        if ($carbonDate?->isTomorrow()) {
            return 'Tomorrow';
        }

        return $carbonDate?->format('D, M j');
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

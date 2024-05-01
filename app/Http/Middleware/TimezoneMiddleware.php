<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use JsonException;
use Sentry;

class TimezoneMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->ip(), ['127.0.0.1', '0.0.0.0'])) {
            return $next($request);
        }

        if (Cookie::get('timezone') === null && ! auth()->check()) {
            $ip = $request->ip();
            $url = "http://ip-api.com/json/$ip";

            try {
                $tz = file_get_contents($url);
                $timezone = json_decode($tz, true, 512, JSON_THROW_ON_ERROR)['timezone'];
                Cookie::queue('timezone', $timezone, 60 * 24 * 30);
            } catch (JsonException $e) {
                Sentry::captureException($e);

                return $next($request);
            }
        }

        return $next($request);
    }
}

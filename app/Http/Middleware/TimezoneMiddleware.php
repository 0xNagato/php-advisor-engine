<?php

namespace App\Http\Middleware;

use Closure;
use ErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use JsonException;

class TimezoneMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Cookie::get('timezone') === null && ! auth()->check()) {
            $ip = $request->ip();
            $url = "http://ip-api.com/json/$ip";

            try {
                $tz = file_get_contents($url);
                $timezone = json_decode($tz, true, 512, JSON_THROW_ON_ERROR)['timezone'];
                Cookie::queue('timezone', $timezone, 60 * 24 * 30);
            } catch (JsonException|ErrorException $e) {
                return $next($request);
            }
        }

        return $next($request);
    }
}

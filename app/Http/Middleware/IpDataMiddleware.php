<?php

namespace App\Http\Middleware;

use App\Services\IPLocationService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Sentry;

class IpDataMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $activeRegions = config('app.active_regions');

        if (! $request->session()->has('region') || ! in_array($request->session()->get('region'), $activeRegions, true)) {
            if (in_array($request->ip(), ['127.0.0.1', '0.0.0.0'])) {
                $request->session()->put('timezone', config('app.default_timezone'));
                $request->session()->put('region', config('app.default_region'));

                return $next($request);
            }

            $ip = $request->ip();

            try {
                $locationData = geoip()->getLocation($ip);
            } catch (Exception $e) {
                Sentry::captureException($e);
                $locationData = app(IPLocationService::class)->getLocationData($ip);
            }

            $region = app(IPLocationService::class)->getClosestRegion($locationData->lat, $locationData->lon);

            $request->session()->put('timezone', $region->timezone);
            $request->session()->put('region', $region->id);

            if (auth()->check()) {
                auth()->user()->update(['region' => $region]);
            }
        }

        return $next($request);
    }
}

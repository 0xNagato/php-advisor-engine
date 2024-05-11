<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Check2fa
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {

            $user = auth()->user();
            $sessionKey = 'twofacode'.$user->id;

            // Check if the device is already verified in the session
            if (session()->has($sessionKey) && session($sessionKey) === true) {
                return $next($request);
            }

            $device = $user->registerDevice();

            if (! $device->verified &&
                ! $request->routeIs('filament.admin.pages.enter2fa') &&
                ! $request->routeIs('filament.admin.auth.logout')) {
                return redirect()->route('filament.admin.pages.enter2fa');
            }

            // Store the verification status in the session
            session()->put($sessionKey, $device->verified);

        }

        return $next($request);
    }
}

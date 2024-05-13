<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $sessionKey = 'twofacode'.auth()->id();

        if ($this->deviceIsVerified($sessionKey)) {
            return $next($request);
        }

        $device = auth()->user()->registerDevice();
        $this->storeDeviceVerificationStatus($sessionKey, $device->verified);

        if (! $device->verified && $this->shouldRedirectTo2fa($request)) {
            return redirect()->route('filament.admin.pages.enter2fa');
        }

        return $next($request);
    }

    protected function deviceIsVerified($sessionKey): bool
    {
        return session()->has($sessionKey) && session($sessionKey) === true;
    }

    protected function storeDeviceVerificationStatus($sessionKey, $verified): void
    {
        session()->put($sessionKey, $verified);
    }

    protected function shouldRedirectTo2fa(Request $request): bool
    {
        return ! $request->routeIs('filament.admin.pages.enter2fa') &&
            ! $request->routeIs('filament.admin.auth.logout');
    }
}

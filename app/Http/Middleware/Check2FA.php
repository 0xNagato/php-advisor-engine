<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Check2FA
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {

            $device = auth()->user()->registerDevice();

            if (! $device->verified &&
                ! $request->routeIs('filament.admin.pages.enter2fa') &&
                ! $request->routeIs('filament.admin.auth.logout')) {
                return redirect()->route('filament.admin.pages.enter2fa');
            }
        }

        return $next($request);
    }
}

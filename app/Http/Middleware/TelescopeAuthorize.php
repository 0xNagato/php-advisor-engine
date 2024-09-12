<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Telescope\Telescope;

class TelescopeAuthorize
{
    public function handle($request, $next)
    {
        Log::info('Telescope Authorize Middleware');

        if (Auth::check() && Auth::user()?->hasRole('super_admin')) {
            Log::info('Telescope Authorize Middleware - User is super admin');

            return $next($request);
        }

        return Telescope::check($request) ? $next($request) : abort(403);
    }
}

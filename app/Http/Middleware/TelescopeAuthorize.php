<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Telescope\Telescope;

class TelescopeAuthorize
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()?->hasActiveRole('super_admin')) {
            return $next($request);
        }

        return Telescope::check($request) ? $next($request) : abort(403);
    }
}

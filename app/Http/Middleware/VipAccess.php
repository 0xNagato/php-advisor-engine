<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VipAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('vip_code')->check()) {
            return redirect()->route('vip.login')
                ->withErrors(['code' => 'Please enter a valid VIP code']);
        }

        return $next($request);
    }
}

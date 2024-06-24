<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyNativeRequests
{
    /**
     * Handle an incoming request.
     *
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->header('User-Agent') !== 'PrimaApp/1.0' &&
            $request->header('Prima-Key') !== config('app.native_key')
        ) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Log;
use Laravel\Telescope\Telescope;

class TelescopeAuthorize
{
    public function handle($request, $next)
    {
        Log::info('Telescope Authorize Middleware');

        return Telescope::check($request) ? $next($request) : abort(403);
    }
}

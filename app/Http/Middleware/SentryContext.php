<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Sentry\State\Scope;

use function Sentry\configureScope;

class SentryContext
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            configureScope(function (Scope $scope): void {
                $user = auth()->user();

                $scope->setUser([
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->name,
                ]);
            });
        }

        return $next($request);
    }
}

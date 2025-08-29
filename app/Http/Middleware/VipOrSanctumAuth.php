<?php

namespace App\Http\Middleware;

use App\Services\VipCodeService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class VipOrSanctumAuth
{
    public function __construct(
        private readonly VipCodeService $vipCodeService
    ) {}

    /**
     * Handle an incoming request.
     *
     * This middleware allows requests that are either:
     * 1. Authenticated via Sanctum (normal users)
     * 2. Authenticated via valid VIP session token (anonymous customers)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        $token = substr($authHeader, 7); // Remove 'Bearer ' prefix

        // Try Sanctum authentication first
        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken) {
            // Set the authenticated user for this request
            Auth::setUser($accessToken->tokenable);
            $request->attributes->set('is_vip_session', false);
            return $next($request);
        }

        // If Sanctum auth failed, try VIP session token
        $sessionData = $this->vipCodeService->validateSessionToken($token);
        if ($sessionData) {
            // Store VIP context in request for use by controllers
            $request->attributes->set('vip_context', $sessionData);
            $request->attributes->set('is_vip_session', true);
            return $next($request);
        }

        // No valid authentication found
        return new JsonResponse(['message' => 'Invalid or expired token.'], 401);
    }
}

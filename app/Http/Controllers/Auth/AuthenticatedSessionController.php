<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        Log::info('API Login Attempt', ['email' => $request->email]);

        if (isPrimaApp()) {
            $request->merge(['remember' => true]);
        }

        $request->authenticate();
        $request->session()->regenerate();
        $user = $request->user();

        $timezone = $user->timezone;
        $region = $user->region;

        // Set session values
        $request->session()->put('timezone', $timezone ?? config('app.default_timezone'));
        $request->session()->put('region', $region ?? config('app.default_region'));
        $token = $user->createToken('prima-mobile');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'role' => Str::of($user->main_role)->snake(),
                    'email' => $user->email,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'timezone' => $timezone,
                    'region' => $region,
                ],
                'token' => $token->plainTextToken,
            ],
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}

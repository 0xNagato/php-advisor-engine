<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        if (isPrimaApp()) {
            $request->merge(['remember' => true]);
        }

        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        $token = $request->user()->createToken('primaVip');

        return response()->json([
            'success' => true,
            'data' => [
                'regions' => Region::active()->get()->transform(fn ($region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                ]),
                'user' => [
                    'id' => $user->id,
                    'role' => $user->main_role,
                    'email' => $user->email,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'timezone' => $user->timezone,
                ],
                'token' => $token->plainTextToken
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

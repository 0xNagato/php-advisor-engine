<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate a user and return a new API token.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string', // e.g., "John's iPhone"
        ]);

        // Find the user by their email address
        $user = User::where('email', $request->email)->first();

        // Verify the user exists and the password is correct
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Create a new Sanctum token for the user
        $token = $user->createToken($request->device_name)->plainTextToken;

        // Return the authenticated user's information and the new token
        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Log the user out and revoke the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request.
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }
}

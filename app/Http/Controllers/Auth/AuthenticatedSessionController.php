<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\IPLocationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sentry;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        Log::info('Login attempt', ['email' => $request->email]);

        if (isPrimaApp()) {
            $request->merge(['remember' => true]);
            Log::info('Prima app login, remember set to true');
        }

        $request->authenticate();
        Log::info('User authenticated successfully');

        $request->session()->regenerate();
        Log::info('Session regenerated');

        $user = $request->user();
        Log::info('User retrieved', ['user_id' => $user->id]);

        // Determine user's region and timezone
        if (config('app.dev_ip_address')) {
            $ip = config('app.dev_ip_address');
            Log::info('Using dev IP address', ['ip' => $ip]);
        } else {
            $ip = $request->ip();
            Log::info('Using request IP address', ['ip' => $ip]);
        }

        if (in_array($ip, ['127.0.0.1', '0.0.0.0'])) {
            $region = config('app.default_region');
            $timezone = config('app.default_timezone');
            Log::info('Using default region and timezone for localhost', ['region' => $region, 'timezone' => $timezone]);
        } else {
            try {
                $locationData = geoip()->getLocation($ip);
                Log::info('Location data retrieved from geoip', ['location' => $locationData]);
            } catch (Exception $e) {
                Log::error('Error retrieving location from geoip', ['error' => $e->getMessage()]);
                Sentry::captureException($e);
                $locationData = app(IPLocationService::class)->getLocationData($ip);
                Log::info('Location data retrieved from IPLocationService', ['location' => $locationData]);
            }

            $regionData = app(IPLocationService::class)->getClosestRegion($locationData->lat, $locationData->lon);
            $region = $regionData->id;
            $timezone = $regionData->timezone;
            Log::info('Region and timezone determined', ['region' => $region, 'timezone' => $timezone]);
        }

        // Update user's region and timezone
        $user->update(['region' => $region, 'timezone' => $timezone]);
        Log::info('User region and timezone updated', ['user_id' => $user->id, 'region' => $region, 'timezone' => $timezone]);

        // Set session values
        $request->session()->put('timezone', $timezone);
        $request->session()->put('region', $region);
        Log::info('Session values set for timezone and region');

        $token = $user->createToken('prima-mobile');
        Log::info('Access token created for user', ['user_id' => $user->id]);

        Log::info('Login process completed successfully', ['user_id' => $user->id]);

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

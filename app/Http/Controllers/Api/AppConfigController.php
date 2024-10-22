<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function __construct(private readonly CacheManager $cache) {}

    public function __invoke(): JsonResponse
    {
        return $this->cache->remember('app_config', 3600, fn () => response()->json([
            'bookings_enabled' => config('app.bookings_enabled'),
            'bookings_disabled_message' => config('app.bookings_disabled_message'),
            'login' => [
                'background_image' => config('app.login.background_image'),
                'text_color' => config('app.login.text_color'),
            ],
        ]));
    }
}

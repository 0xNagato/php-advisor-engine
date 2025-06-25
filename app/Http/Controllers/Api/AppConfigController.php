<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\OpenApi\Responses\AppConfigResponse;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\JsonResponse;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Response;

// #[OpenApi\PathItem]
class AppConfigController extends Controller
{
    public function __construct(private readonly CacheManager $cache) {}

    /**
     * Retrieve the application configuration.
     *
     * This endpoint provides the application-wide configuration settings
     * such as booking status and login page customization.
     */
    #[OpenApi\Operation(
        tags: ['App Config'],
    )]
    #[Response(factory: AppConfigResponse::class)]
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VipSession;
use App\OpenApi\RequestBodies\VipSessionCreateRequestBody;
use App\OpenApi\RequestBodies\VipSessionValidateRequestBody;
use App\OpenApi\Responses\VipSessionAnalyticsResponse;
use App\OpenApi\Responses\VipSessionCreateResponse;
use App\OpenApi\Responses\VipSessionValidateResponse;
use App\Services\VipCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\RequestBody;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

// #[OpenApi\PathItem]
class VipSessionController extends Controller
{
    public function __construct(
        private readonly VipCodeService $vipCodeService
    ) {}

    /**
     * Create a VIP session from a VIP code.
     */
    #[OpenApi\Operation(
        tags: ['VIP Sessions'],
    )]
    #[RequestBody(factory: VipSessionCreateRequestBody::class)]
    #[OpenApiResponse(factory: VipSessionCreateResponse::class)]
    public function createSession(Request $request): JsonResponse
    {
        $request->validate([
            'vip_code' => ['required', 'string', 'min:4', 'max:12'],
        ]);

        $sessionData = $this->vipCodeService->createVipSession($request->input('vip_code'));

        if (! $sessionData) {
            return response()->json([
                'message' => 'Unable to create session',
            ], 500);
        }

        $response = [
            'success' => true,
            'data' => [
                'session_token' => $sessionData['token'],
                'expires_at' => $sessionData['expires_at'],
                'is_demo' => $sessionData['is_demo'],
            ],
        ];

        // Add a demo message if in demo mode
        if ($sessionData['is_demo']) {
            $response['data']['demo_message'] = $sessionData['demo_message'];
        } else {
            // Add VIP code info for valid sessions
            $response['data']['vip_code'] = [
                'id' => $sessionData['vip_code']->id,
                'code' => $sessionData['vip_code']->code,
                'concierge' => [
                    'id' => $sessionData['vip_code']->concierge->id,
                    'name' => $sessionData['vip_code']->concierge->user->name,
                    'hotel_name' => $sessionData['vip_code']->concierge->hotel_name,
                ],
            ];
        }

        return response()->json($response);
    }

    /**
     * Validate a VIP session token.
     */
    #[OpenApi\Operation(
        tags: ['VIP Sessions'],
    )]
    #[RequestBody(factory: VipSessionValidateRequestBody::class)]
    #[OpenApiResponse(factory: VipSessionValidateResponse::class)]
    public function validateSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => ['required', 'string'],
        ]);

        $sessionData = $this->vipCodeService->validateSessionToken($request->input('session_token'));

        if (! $sessionData) {
            return response()->json([
                'success' => false,
                'data' => [
                    'valid' => false,
                    'message' => 'Invalid or expired session token',
                ],
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => true,
                'is_demo' => $sessionData['is_demo'],
                'session' => [
                    'id' => $sessionData['session']->id,
                    'expires_at' => $sessionData['session']->expires_at->toISOString(),
                ],
                'vip_code' => [
                    'id' => $sessionData['vip_code']->id,
                    'code' => $sessionData['vip_code']->code,
                    'concierge' => [
                        'id' => $sessionData['vip_code']->concierge->id,
                        'name' => $sessionData['vip_code']->concierge->user->name,
                        'hotel_name' => $sessionData['vip_code']->concierge->hotel_name,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get session analytics (admin only)
     */
    #[OpenApi\Operation(
        tags: ['VIP Sessions'],
        security: 'BearerTokenSecurityScheme'
    )]
    #[OpenApiResponse(factory: VipSessionAnalyticsResponse::class)]
    public function getSessionAnalytics(Request $request): JsonResponse
    {
        // This would require admin authentication
        // For now, return basic stats

        $totalSessions = VipSession::query()->count();
        $activeSessions = VipSession::query()->where('expires_at', '>', now())->count();
        $expiredSessions = VipSession::query()->where('expires_at', '<=', now())->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sessions' => $totalSessions,
                'active_sessions' => $activeSessions,
                'expired_sessions' => $expiredSessions,
                'session_creation_rate' => [
                    'last_24h' => VipSession::query()->where('created_at', '>', now()->subDay())->count(),
                    'last_7d' => VipSession::query()->where('created_at', '>', now()->subWeek())->count(),
                    'last_30d' => VipSession::query()->where('created_at', '>', now()->subMonth())->count(),
                ],
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VipCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VipSessionController extends Controller
{
    public function __construct(
        private readonly VipCodeService $vipCodeService
    ) {}

    /**
     * Create a VIP session from a VIP code
     */
    public function createSession(Request $request): JsonResponse
    {
        $request->validate([
            'vip_code' => 'required|string|min:4|max:12',
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

        // Add demo message if in demo mode
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
     * Validate a VIP session token
     */
    public function validateSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|string',
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
    public function getSessionAnalytics(Request $request): JsonResponse
    {
        // This would require admin authentication
        // For now, just return basic stats

        $totalSessions = \App\Models\VipSession::count();
        $activeSessions = \App\Models\VipSession::where('expires_at', '>', now())->count();
        $expiredSessions = \App\Models\VipSession::where('expires_at', '<=', now())->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sessions' => $totalSessions,
                'active_sessions' => $activeSessions,
                'expired_sessions' => $expiredSessions,
                'session_creation_rate' => [
                    'last_24h' => \App\Models\VipSession::where('created_at', '>', now()->subDay())->count(),
                    'last_7d' => \App\Models\VipSession::where('created_at', '>', now()->subWeek())->count(),
                    'last_30d' => \App\Models\VipSession::where('created_at', '>', now()->subMonth())->count(),
                ],
            ],
        ]);
    }
}

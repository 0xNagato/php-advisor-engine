<?php

namespace App\Http\Controllers\Api;

use App\Enums\VipCodeTemplate;
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
use Illuminate\Support\Facades\Storage;
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
            'query_params' => ['nullable', 'array'],
        ]);

        $sessionData = $this->vipCodeService->createVipSession(
            $request->input('vip_code'),
            $request->input('query_params')
        );

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
                'flow_type' => $this->determineFlowType($sessionData),
                'template' => $this->getVipCodeTemplate($sessionData['vip_code']),
                'vip_code' => [
                    'id' => $sessionData['vip_code']->id,
                    'code' => $sessionData['vip_code']->code,
                    'concierge' => array_filter([
                        'id' => $sessionData['vip_code']->concierge->id,
                        'name' => $sessionData['vip_code']->concierge->user->name,
                        'hotel_name' => $sessionData['vip_code']->concierge->hotel_name,
                        'branding' => $this->prepareBrandingForApi($sessionData['vip_code']->concierge, $sessionData['vip_code']),
                    ]),
                    'region' => $this->getVipCodeRegion($sessionData['vip_code']),
                ],
            ],
        ];

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
                'flow_type' => $this->determineFlowType($sessionData),
                'template' => $this->getVipCodeTemplate($sessionData['vip_code']),
                'session' => [
                    'id' => $sessionData['session']->id,
                    'expires_at' => $sessionData['session']->expires_at->toISOString(),
                ],
                'vip_code' => [
                    'id' => $sessionData['vip_code']->id,
                    'code' => $sessionData['vip_code']->code,
                    'region' => $this->getVipCodeRegion($sessionData['vip_code']),
                    'concierge' => array_filter([
                        'id' => $sessionData['vip_code']->concierge->id,
                        'name' => $sessionData['vip_code']->concierge->user->name,
                        'hotel_name' => $sessionData['vip_code']->concierge->hotel_name,
                        'branding' => $this->prepareBrandingForApi($sessionData['vip_code']->concierge, $sessionData['vip_code']),
                    ]),
                ],
            ],
        ]);
    }

    /**
     * Track analytics event for a VIP session
     */
    public function trackEvent(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => ['required', 'string'],
            'event' => ['required', 'string'],
            'data' => ['array'],
        ]);

        $success = $this->vipCodeService->trackEvent(
            $request->input('session_token'),
            $request->input('event'),
            $request->input('data', [])
        );

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired session token',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event tracked successfully',
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
        // Enhanced analytics with conversion funnel data
        $totalSessions = VipSession::query()->count();
        $activeSessions = VipSession::query()->where('expires_at', '>', now())->count();
        $expiredSessions = VipSession::query()->where('expires_at', '<=', now())->count();

        // Conversion funnel analytics
        $sessionsLast24h = VipSession::query()->where('started_at', '>', now()->subDay())->count();
        $sessionsLast7d = VipSession::query()->where('started_at', '>', now()->subWeek())->count();
        $sessionsLast30d = VipSession::query()->where('started_at', '>', now()->subMonth())->count();

        // Average session duration
        $avgSessionDuration = VipSession::query()
            ->whereNotNull('started_at')
            ->whereNotNull('last_activity_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, last_activity_at)) as avg_duration')
            ->value('avg_duration');

        return response()->json([
            'success' => true,
            'data' => [
                'total_sessions' => $totalSessions,
                'active_sessions' => $activeSessions,
                'expired_sessions' => $expiredSessions,
                'session_creation_rate' => [
                    'last_24h' => $sessionsLast24h,
                    'last_7d' => $sessionsLast7d,
                    'last_30d' => $sessionsLast30d,
                ],
                'average_session_duration_minutes' => round($avgSessionDuration ?? 0, 2),
                'top_vip_codes' => VipSession::query()
                    ->with('vipCode')
                    ->selectRaw('vip_code_id, COUNT(*) as session_count')
                    ->where('started_at', '>', now()->subWeek())
                    ->groupBy('vip_code_id')
                    ->orderByDesc('session_count')
                    ->limit(10)
                    ->get()
                    ->map(fn ($session) => [
                        'vip_code' => $session->vipCode->code,
                        'concierge' => $session->vipCode->concierge->user->name,
                        'session_count' => $session->session_count,
                    ]),
            ],
        ]);
    }

    /**
     * Prepare branding data for API response
     */
    private function prepareBrandingForApi($concierge, $vipCode = null): ?array
    {
        $branding = null;

        // First check VIP code-level branding (if VIP code is provided)
        if ($vipCode && $vipCode->branding && $vipCode->branding->hasBranding()) {
            $branding = $vipCode->branding;
        }
        // Fall back to concierge-level branding
        elseif ($concierge->branding && $concierge->branding->hasBranding()) {
            $branding = $concierge->branding;
        }

        // Only return branding if any data is configured
        if (! $branding) {
            return null;
        }

        $apiData = $branding->toApiResponse();

        // Convert logo_url to absolute URL if it exists
        if ($apiData['logo_url']) {
            $apiData['logo_url'] = Storage::disk('do')->url($apiData['logo_url']);
        }

        return $apiData;
    }

    /**
     * Get VIP code region from venue collection.
     */
    private function getVipCodeRegion($vipCode): ?string
    {
        // Check for VIP code-level collection first
        $collection = $vipCode->venueCollections()->where('is_active', true)->first();

        if ($collection) {
            return $collection->region;
        }

        // Fall back to concierge-level collection
        $conciergeCollection = $vipCode->concierge->venueCollections()->where('is_active', true)->first();

        return $conciergeCollection?->region;
    }

    /**
     * Get VIP code template from branding.
     */
    private function getVipCodeTemplate($vipCode): string
    {
        // First check VIP code level branding
        if ($vipCode->branding && $vipCode->branding->template) {
            return $vipCode->branding->template->value;
        }

        // Fall back to concierge level branding
        if ($vipCode->concierge->branding && $vipCode->concierge->branding->template) {
            return $vipCode->concierge->branding->template->value;
        }

        // Default template
        return VipCodeTemplate::AVAILABILITY_CALENDAR->value;
    }

    /**
     * Determine the flow type for a VIP session.
     */
    private function determineFlowType($sessionData): string
    {
        // First check VIP code-level branding
        if ($sessionData['vip_code']->branding && $sessionData['vip_code']->branding->hasBranding()) {
            return 'white_label';
        }

        // Fall back to concierge-level branding
        $branding = $sessionData['vip_code']->concierge->branding;
        if ($branding && $branding->hasBranding()) {
            return 'white_label';
        }

        return 'standard';
    }
}

<?php

namespace App\Services;

use App\Models\VipCode;
use App\Models\VipSession;
use Illuminate\Support\Facades\Log;

class VipCodeService
{
    public function findByCode(string $code): ?VipCode
    {
        return VipCode::with('concierge.user')
            ->active()
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();
    }

    /**
     * Create a VIP session and return session token
     */
    public function createVipSession(string $code): ?array
    {
        $vipCode = $this->findByCode($code);

        if (! $vipCode) {
            // Log failed attempt for analytics
            $this->logVipSessionEvent('vip_session_invalid_code_attempted', [
                'code' => $code,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Return demo session for fallback
            return $this->createDemoSession();
        }

        // Clean up expired sessions for this VIP code
        $vipCode->cleanExpiredSessions();

        // Generate new session token
        $token = $vipCode->generateSessionToken();

        // Log successful session creation for analytics
        $this->logVipSessionEvent('vip_session_created', [
            'vip_code_id' => $vipCode->id,
            'concierge_id' => $vipCode->concierge_id,
            'code' => $code,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'token' => $token,
            'expires_at' => now()->addHours(24)->toISOString(),
            'vip_code' => $vipCode,
            'is_demo' => false,
        ];
    }

    /**
     * Validate a VIP session token
     */
    public function validateSessionToken(string $token): ?array
    {
        $hashedToken = hash('sha256', $token);

        $session = VipSession::with('vipCode.concierge.user')
            ->where('token', $hashedToken)
            ->where('expires_at', '>', now())
            ->first();

        if (! $session) {
            // Log failed validation for analytics
            $this->logVipSessionEvent('vip_session_invalid_token_attempted', [
                'token_hash' => substr($hashedToken, 0, 8).'...', // Only log partial hash for security
                'ip_address' => request()->ip(),
            ]);

            return null;
        }

        // Log successful validation
        $this->logVipSessionEvent('vip_session_validated', [
            'vip_code_id' => $session->vip_code_id,
            'concierge_id' => $session->vipCode->concierge_id,
            'session_id' => $session->id,
        ]);

        return [
            'session' => $session,
            'vip_code' => $session->vipCode,
            'is_demo' => false,
        ];
    }

    /**
     * Create demo session for fallback behavior
     */
    private function createDemoSession(): array
    {
        // Log demo session creation for analytics
        $this->logVipSessionEvent('vip_demo_session_created', [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'token' => 'demo_'.time(),
            'expires_at' => now()->addHours(24)->toISOString(),
            'vip_code' => null,
            'is_demo' => true,
            'demo_message' => 'You are viewing in demo mode. Some features may be limited.',
        ];
    }

    /**
     * Log VIP session events for analytics
     */
    private function logVipSessionEvent(string $event, array $data = []): void
    {
        activity()
            ->withProperties(array_merge($data, [
                'event_type' => $event,
                'timestamp' => now()->toISOString(),
            ]))
            ->log($event);

        // Also log to application log for debugging
        Log::info("VIP Session Event: {$event}", $data);
    }

    /**
     * Clean up all expired sessions (can be run via scheduled task)
     */
    public function cleanupExpiredSessions(): int
    {
        $count = VipSession::where('expires_at', '<', now())->count();
        VipSession::where('expires_at', '<', now())->delete();

        if ($count > 0) {
            $this->logVipSessionEvent('vip_sessions_cleanup', [
                'expired_sessions_deleted' => $count,
            ]);
        }

        return $count;
    }
}

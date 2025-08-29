<?php

namespace App\Services;

use App\Models\VipCode;
use App\Models\VipSession;
use Illuminate\Support\Facades\Log;

class VipCodeService
{
    /**
     * Get the configured fallback VIP code
     */
    private function getFallbackCode(): ?string
    {
        return config('app.vip.fallback_code');
    }

    /**
     * Get the configured session duration in hours
     */
    private function getSessionDurationHours(): int
    {
        return config('app.vip.session_duration_hours', 24);
    }

    public function findByCode(string $code): ?VipCode
    {
        // Don't load the user relationship to avoid session interference
        // The user relationship is not needed for VIP session creation
        return VipCode::with('concierge')
            ->active()
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();
    }

    /**
     * Create a VIP analytics session for tracking customer journey
     */
    public function createVipSession(string $code): ?array
    {
        $vipCode = $this->findByCode($code);

        if (! $vipCode) {
            // Try fallback code if configured
            $fallbackCode = $this->getFallbackCode();
            if ($fallbackCode) {
                $vipCode = $this->findByCode($fallbackCode);

                if ($vipCode) {
                    $this->logVipSessionEvent('vip_session_fallback_code_used', [
                        'original_code' => $code,
                        'fallback_code' => $fallbackCode,
                        'vip_code_id' => $vipCode->id,
                        'ip_address' => request()->ip(),
                    ]);
                }
            }

            if (! $vipCode) {
                $this->logVipSessionEvent('vip_session_invalid_code_attempted', [
                    'code' => $code,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
                return null;
            }
        }

        // Clean up expired sessions for this VIP code
        $vipCode->cleanExpiredSessions();

        // Create anonymous analytics tracking token
        $sessionDuration = $this->getSessionDurationHours();
        $sessionToken = $this->generateAnonymousTrackingToken($vipCode, $code);

        // Store analytics session for conversion tracking
        $session = VipSession::query()->create([
            'vip_code_id' => $vipCode->id,
            'token' => $sessionToken,
            'expires_at' => now()->addHours($sessionDuration),
            'sanctum_token_id' => null, // Analytics sessions are never linked to user accounts
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'started_at' => now(),
        ]);

        // Log analytics session creation
        $this->logVipSessionEvent('vip_session_created', [
            'session_id' => $session->id,
            'vip_code_id' => $vipCode->id,
            'concierge_id' => $vipCode->concierge_id,
            'code' => $code,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Load the user relationship only for the API response
        $vipCode->load('concierge.user');

        return [
            'token' => $sessionToken,
            'expires_at' => now()->addHours($sessionDuration)->toISOString(),
            'vip_code' => $vipCode,
        ];
    }

    /**
     * Generate a secure anonymous tracking token for analytics
     */
    private function generateAnonymousTrackingToken(VipCode $vipCode, string $code): string
    {
        // Generate a unique, secure token for analytics tracking (not authentication)
        $randomBytes = random_bytes(32);
        $timestamp = time();
        $vipCodeHash = hash('sha256', $vipCode->id . $code);
        $sessionId = uniqid('vip_', true);

        return hash('sha256', $randomBytes . $timestamp . $vipCodeHash . $sessionId . config('app.key'));
    }

    /**
     * Validate a VIP analytics session token
     */
    public function validateSessionToken(string $token): ?array
    {
        // Find analytics session by token
        $session = VipSession::with('vipCode.concierge.user')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $session) {
            $this->logVipSessionEvent('vip_session_invalid_token_attempted', [
                'token_hash' => substr(hash('sha256', $token), 0, 8).'...', // Only log partial hash for security
                'ip_address' => request()->ip(),
            ]);

            return null;
        }

        // Update last activity for analytics
        $session->update(['last_activity_at' => now()]);

        // Log successful validation for analytics
        $this->logVipSessionEvent('vip_session_validated', [
            'session_id' => $session->id,
            'vip_code_id' => $session->vip_code_id,
            'concierge_id' => $session->vipCode->concierge_id,
        ]);

        return [
            'session' => $session,
            'vip_code' => $session->vipCode,
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

        // Also log to the application log for debugging
        Log::info("VIP Session Event: {$event}", $data);
    }

    /**
     * Track analytics event for a VIP session
     */
    public function trackEvent(string $token, string $event, array $data = []): bool
    {
        $session = VipSession::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $session) {
            return false;
        }

        // Update last activity
        $session->update(['last_activity_at' => now()]);

        // Log the analytics event
        $this->logVipSessionEvent($event, array_merge($data, [
            'session_id' => $session->id,
            'vip_code_id' => $session->vip_code_id,
            'concierge_id' => $session->vipCode->concierge_id,
        ]));

        return true;
    }

    /**
     * Clean up expired analytics sessions
     */
    public function cleanupExpiredSessions(): int
    {
        // Get expired VIP sessions
        $expiredSessions = VipSession::query()->where('expires_at', '<', now())->get();
        $count = $expiredSessions->count();

        // Delete expired analytics session records
        VipSession::query()->where('expires_at', '<', now())->delete();

        if ($count > 0) {
            $this->logVipSessionEvent('vip_sessions_cleanup', [
                'expired_sessions_deleted' => $count,
            ]);
        }

        return $count;
    }
}

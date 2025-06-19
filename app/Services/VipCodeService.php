<?php

namespace App\Services;

use App\Models\User;
use App\Models\VipCode;
use App\Models\VipSession;
use Illuminate\Support\Facades\Log;

class VipCodeService
{
    private const DEMO_USER_EMAIL = 'demo@primavip.co';

    public function findByCode(string $code): ?VipCode
    {
        return VipCode::with('concierge.user')
            ->active()
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();
    }

    /**
     * Create a VIP session and return Sanctum token
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

        // Create Sanctum token for the concierge user
        $user = $vipCode->concierge->user;
        $token = $user->createToken('vip-session-'.$code, ['*'], now()->addHours(24));

        // Store session tracking info
        VipSession::create([
            'vip_code_id' => $vipCode->id,
            'token' => hash('sha256', $token->plainTextToken), // Store hash for tracking
            'expires_at' => now()->addHours(24),
            'sanctum_token_id' => $token->accessToken->id, // Link to Sanctum token
        ]);

        // Log successful session creation for analytics
        $this->logVipSessionEvent('vip_session_created', [
            'vip_code_id' => $vipCode->id,
            'concierge_id' => $vipCode->concierge_id,
            'code' => $code,
            'sanctum_token_id' => $token->accessToken->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'token' => $token->plainTextToken, // Return the actual Sanctum token
            'expires_at' => now()->addHours(24)->toISOString(),
            'vip_code' => $vipCode,
            'is_demo' => false,
        ];
    }

    /**
     * Validate a VIP session token (now validates Sanctum tokens)
     */
    public function validateSessionToken(string $token): ?array
    {
        // Find the Sanctum token
        $sanctumToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (! $sanctumToken || $sanctumToken->expires_at < now()) {
            // Log failed validation for analytics
            $this->logVipSessionEvent('vip_session_invalid_token_attempted', [
                'token_hash' => substr(hash('sha256', $token), 0, 8).'...', // Only log partial hash for security
                'ip_address' => request()->ip(),
            ]);

            return null;
        }

        // Find our VIP session record
        $session = VipSession::with('vipCode.concierge.user')
            ->where('sanctum_token_id', $sanctumToken->id)
            ->first();

        if (! $session) {
            // This might be a demo token or other non-VIP token
            return null;
        }

        // Log successful validation
        $this->logVipSessionEvent('vip_session_validated', [
            'vip_code_id' => $session->vip_code_id,
            'concierge_id' => $session->vipCode->concierge_id,
            'session_id' => $session->id,
            'sanctum_token_id' => $sanctumToken->id,
        ]);

        return [
            'session' => $session,
            'vip_code' => $session->vipCode,
            'is_demo' => false,
        ];
    }

    /**
     * Create demo session for fallback behavior (uses demo user's Sanctum token)
     */
    private function createDemoSession(): array
    {
        // Get or create demo user
        $demoUser = User::where('email', self::DEMO_USER_EMAIL)->first();

        if (! $demoUser) {
            throw new \Exception('Demo user not found. Please run: php artisan vip:setup-demo-user');
        }

        // Create Sanctum token for demo user
        $token = $demoUser->createToken('demo-session-'.time(), ['*'], now()->addHours(24));

        // Log demo session creation for analytics
        $this->logVipSessionEvent('vip_demo_session_created', [
            'demo_user_id' => $demoUser->id,
            'sanctum_token_id' => $token->accessToken->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'token' => $token->plainTextToken, // Return actual Sanctum token
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
        // Get expired VIP sessions
        $expiredSessions = VipSession::where('expires_at', '<', now())->get();
        $count = $expiredSessions->count();

        foreach ($expiredSessions as $session) {
            // Delete the corresponding Sanctum token
            if ($session->sanctum_token_id) {
                \Laravel\Sanctum\PersonalAccessToken::where('id', $session->sanctum_token_id)->delete();
            }
        }

        // Delete expired VIP session records
        VipSession::where('expires_at', '<', now())->delete();

        if ($count > 0) {
            $this->logVipSessionEvent('vip_sessions_cleanup', [
                'expired_sessions_deleted' => $count,
            ]);
        }

        return $count;
    }
}

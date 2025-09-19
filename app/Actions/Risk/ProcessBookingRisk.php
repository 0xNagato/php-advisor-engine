<?php

namespace App\Actions\Risk;

use App\Models\Booking;
use App\Models\RiskAuditLog;
use App\Notifications\Risk\BookingOnRiskHold;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;

class ProcessBookingRisk
{
    use AsAction;

    /**
     * Process risk scoring for a booking and apply holds if necessary
     */
    public function handle(Booking $booking): void
    {
        // Check if risk screening is enabled
        $riskScreeningEnabled = config('app.risk_screening_enabled', true);

        // Skip if risk screening is disabled
        if (!$riskScreeningEnabled) {
            if (config('app.debug')) {
                Log::debug('Risk screening skipped - disabled in config', [
                    'booking_id' => $booking->id,
                ]);
            }
            return;
        }

        if (config('app.debug')) {
            Log::debug('Starting risk screening', [
                'booking_id' => $booking->id,
                'guest_name' => $booking->guest_first_name . ' ' . $booking->guest_last_name,
            ]);
        }

        // Get IP and user agent from request if available
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        // Store IP and user agent on booking
        if ($ipAddress || $userAgent) {
            $booking->update([
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent
            ]);
            // Refresh to ensure we have the latest data
            $booking->refresh();
        }

        // Score the booking
        $result = ScoreBookingSuspicion::run(
            $booking->guest_email ?? '',
            $booking->guest_phone ?? '',
            $booking->guest_name ?? '',
            $ipAddress,
            $userAgent,
            $booking->notes,
            $booking
        );

        $softThreshold = config('app.ai_screening_threshold_soft', 30);
        $hardThreshold = config('app.ai_screening_threshold_hard', 70);

        // Determine risk state
        $riskState = null;
        if ($result['score'] >= $hardThreshold) {
            $riskState = 'hard';
        } elseif ($result['score'] >= $softThreshold) {
            $riskState = 'soft';
        }

        // Create risk metadata
        $riskMetadata = new \App\Data\RiskMetadata(
            totalScore: $result['score'],
            breakdown: $result['features']['breakdown'] ?? null,
            reasons: $result['reasons'],
            features: $result['features'],
            analyzedAt: now()->toISOString(),
            llmUsed: $result['features']['llm_used'] ?? false,
            llmResponse: $result['features']['llm_response'] ?? null
        );

        // Update booking with risk score and metadata
        $booking->update([
            'risk_score' => $result['score'],
            'risk_state' => $riskState,
            'risk_reasons' => $result['reasons'],
            'risk_metadata' => $riskMetadata,
        ]);

        if (config('app.debug')) {
            Log::debug('Risk score calculated', [
                'booking_id' => $booking->id,
                'score' => $result['score'],
                'risk_state' => $riskState,
                'reasons' => $result['reasons'],
                'saved_risk_score' => $booking->risk_score,
            ]);
        }

        // Create audit log
        RiskAuditLog::createEntry(
            $booking->id,
            RiskAuditLog::EVENT_SCORED,
            [
                'score' => $result['score'],
                'reasons' => $result['reasons'],
                'features' => $result['features'],
                'threshold_soft' => $softThreshold,
                'threshold_hard' => $hardThreshold,
                'risk_state' => $riskState,
            ],
            null,
            $ipAddress
        );

        // Send Slack notification based on risk level and configuration
        $shouldSendSlack = $riskState || config('app.send_low_risk_bookings_to_slack', false);

        if ($shouldSendSlack) {
            try {
                SendRiskAlertToSlack::run($booking, $result);
            } catch (\Exception $e) {
                Log::error('Failed to send Slack alert', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // If booking is on hold, handle accordingly
        if ($riskState) {
            $this->handleRiskHold($booking, $riskState, $result);
        } else {
            // Auto-approve if score is low
            RiskAuditLog::createEntry(
                $booking->id,
                RiskAuditLog::EVENT_AUTO_APPROVED,
                [
                    'score' => $result['score'],
                    'reasons' => $result['reasons'],
                ],
                null,
                $ipAddress
            );
        }
    }

    /**
     * Handle a booking that's been placed on risk hold
     */
    protected function handleRiskHold(Booking $booking, string $riskState, array $result): void
    {
        // Create hold audit log
        RiskAuditLog::createEntry(
            $booking->id,
            RiskAuditLog::EVENT_AUTO_HELD,
            [
                'risk_state' => $riskState,
                'score' => $result['score'],
                'reasons' => $result['reasons'],
            ],
            null,
            $booking->ip_address
        );

        // Update booking status to indicate it needs review
        if ($booking->status === 'pending') {
            $booking->update([
                'status' => 'review_pending'
            ]);
        }

        // Slack notification already sent in parent method

        Log::info('Booking placed on risk hold', [
            'booking_id' => $booking->id,
            'risk_state' => $riskState,
            'score' => $result['score'],
            'reasons' => $result['reasons'],
        ]);
    }
}
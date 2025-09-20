<?php

namespace App\Actions\Risk;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\RiskAuditLog;
use App\Notifications\Booking\BookingRejectedDueToRisk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class RejectRiskReview
{
    use AsAction;

    /**
     * Reject a booking that was on risk hold
     */
    public function handle(Booking $booking, string $reason, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($booking, $reason, $userId) {
            $previousState = $booking->risk_state;

            // Update booking
            $booking->update([
                'reviewed_at' => now(),
                'reviewed_by' => $userId ?? auth()->id(),
                'status' => BookingStatus::CANCELLED->value,
                'refund_reason' => 'Risk review rejection: ' . $reason,
            ]);

            // Create audit log
            RiskAuditLog::createEntry(
                $booking->id,
                RiskAuditLog::EVENT_REJECTED,
                [
                    'previous_state' => $previousState,
                    'score' => $booking->risk_score,
                    'rejection_reason' => $reason,
                    'rejected_by' => $userId ?? auth()->id(),
                ],
                $userId
            );

            // Send Slack notification
            try {
                SendRiskRejectionToSlack::run($booking, $reason);
            } catch (\Exception $e) {
                Log::error('Failed to send Slack rejection notification', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Optionally send customer SMS if phone seems legitimate
            if ($booking->guest_phone && $booking->risk_score < 80) {
                try {
                    $booking->notify(new BookingRejectedDueToRisk($reason));
                } catch (\Exception $e) {
                    Log::error('Failed to send customer rejection notification', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return true;
        });
    }
}
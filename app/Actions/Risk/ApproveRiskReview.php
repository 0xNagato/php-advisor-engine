<?php

namespace App\Actions\Risk;

use App\Actions\Booking\SendConfirmationToVenueContacts;
use App\Enums\BookingStatus;
use App\Events\BookingConfirmed;
use App\Models\Booking;
use App\Models\RiskAuditLog;
use App\Notifications\Booking\CustomerBookingConfirmed;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ApproveRiskReview
{
    use AsAction;

    /**
     * Approve a booking that was on risk hold
     */
    public function handle(Booking $booking, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($booking, $userId) {
            // Update booking
            $booking->update([
                'risk_state' => null,
                'reviewed_at' => now(),
                'reviewed_by' => $userId ?? auth()->id(),
                'status' => BookingStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);

            // Create audit log
            RiskAuditLog::createEntry(
                $booking->id,
                RiskAuditLog::EVENT_APPROVED,
                [
                    'previous_state' => $booking->getOriginal('risk_state'),
                    'score' => $booking->risk_score,
                    'approved_by' => $userId ?? auth()->id(),
                ],
                $userId
            );

            // Send notifications that were held
            $booking->notify(new CustomerBookingConfirmed);
            SendConfirmationToVenueContacts::run($booking);

            // Dispatch booking confirmed event
            BookingConfirmed::dispatch($booking->load('schedule', 'venue'));

            return true;
        });
    }
}
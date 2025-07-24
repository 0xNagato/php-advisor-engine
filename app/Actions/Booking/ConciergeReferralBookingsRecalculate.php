<?php

namespace App\Actions\Booking;

use App\Enums\EarningType;
use App\Models\Booking;
use App\Services\Booking\BookingCalculationService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class ConciergeReferralBookingsRecalculate
{
    use AsAction;

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        // Confirmed Bookings Without Referral Earnings
        $bookings = Booking::with([
            'venue.user',
            'concierge.user',
            'concierge.referringConcierge.referringConcierge',
        ])
            ->confirmed()
            ->whereHas('concierge.referringConcierge')
            ->whereDoesntHave('earnings', function ($query) {
                $query->whereIn('type', [
                    EarningType::CONCIERGE_REFERRAL_1->value,
                    EarningType::CONCIERGE_REFERRAL_2->value,
                ]);
            })
            ->get();

        $affectedRecords = 0;

        $bookings->each(function ($booking) use (&$affectedRecords) {
            DB::beginTransaction();
            try {
                // Delete earnings
                $booking->earnings()->delete();

                // Recalculate
                app(BookingCalculationService::class)->calculateEarnings($booking);

                // Confirmed earnings for all confirmed bookings that aren't cancelled or refunded
                if (!in_array($booking->status, ['cancelled', 'refunded'])) {
                    $booking->earnings()->update(['confirmed_at' => $booking->confirmed_at]);
                }

                // Fix booking partner_concierge_id
                if (blank($booking->concierge->user->partner_referral_id) && filled($booking->partner_concierge_id)) {
                    $booking->update(['partner_concierge_id' => null]);
                }

                // Log
                activity()
                    ->performedOn($booking)
                    ->log('Concierge Referral earnings re-calculated');
                DB::commit();
                $affectedRecords++;
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Concierge Referral earnings could not be re-calculated', [
                    'bid' => $booking->id,
                    'error' => $e,
                ]);
            }
        });

        return $affectedRecords;
    }
}

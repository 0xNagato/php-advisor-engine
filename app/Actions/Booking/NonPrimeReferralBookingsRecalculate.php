<?php

namespace App\Actions\Booking;

use App\Enums\EarningType;
use App\Models\Booking;
use App\Services\Booking\BookingCalculationService;
use Exception;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class NonPrimeReferralBookingsRecalculate
{
    use AsAction;

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        // Look for non-prime bookings that have not been updated
        $bookings = Booking::query()
            ->with([
                'venue.user',
                'concierge.user',
                'concierge.referringConcierge.referringConcierge',
            ])
            ->where('is_prime', 0)
            ->where(function ($query) {
                $query->where('partner_concierge_fee', 0)
                    ->where('partner_venue_fee', 0);
            })
            ->whereDoesntHave('earnings', function (Builder $query) {
                $query->where('type', EarningType::PARTNER_VENUE->value);
            })
            ->confirmed()
            ->get();

        $affectedRecords = 0;

        $bookings->each(function ($booking) use (&$affectedRecords) {
            DB::beginTransaction();
            try {
                // Delete earnings
                $booking->earnings()->delete();

                // Recalculate
                app(BookingCalculationService::class)->calculateEarnings($booking);

                // Log
                activity()
                    ->performedOn($booking)
                    ->log('Non-prime referral earnings re-calculated');
                DB::commit();
                $affectedRecords++;
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Non-prime referral earnings could not be re-calculated', [
                    'bid' => $booking->id,
                    'error' => $e,
                ]);
            }
        });

        return $affectedRecords;
    }
}

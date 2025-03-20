<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Services\Booking\BookingCalculationService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class PartnerReferralBookingsRecalculate
{
    use AsAction;

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        // Look for bookings with wrong partner concierge id
        $bookings = Booking::query()
            ->with([
                'venue.user',
                'concierge.user',
                'concierge.referringConcierge.referringConcierge',
            ])
            ->where('is_prime', 1)
            ->whereIn('status', ['confirmed', 'venue_confirmed'])
            ->whereHas('concierge.user', function ($query) {
                $query->where(function ($query) {
                    $query
                        ->whereColumn('partner_concierge_id', '<>', 'partner_referral_id')
                        ->orWhere(function ($query) {
                            $query
                                ->whereNull('partner_concierge_id')
                                ->whereNotNull('partner_referral_id');
                        })
                        ->orWhere(function ($query) {
                            $query
                                ->whereNotNull('partner_concierge_id')
                                ->whereNull('partner_referral_id');
                        });
                });
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

                // Confirmed earnings
                if ($booking->is_prime) {
                    $booking->earnings()->update(['confirmed_at' => $booking->confirmed_at]);
                }

                // Log
                activity()
                    ->performedOn($booking)
                    ->log('Partner Referral earnings re-calculated');
                DB::commit();
                $affectedRecords++;
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Partner Referral earnings could not be re-calculated', [
                    'bid' => $booking->id,
                    'error' => $e,
                ]);
            }
        });

        return $affectedRecords;
    }
}

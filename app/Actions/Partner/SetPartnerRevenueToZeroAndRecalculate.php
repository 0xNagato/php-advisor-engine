<?php

namespace App\Actions\Partner;

use App\Models\Booking;
use App\Models\Partner;
use App\Services\Booking\BookingCalculationService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class SetPartnerRevenueToZeroAndRecalculate
{
    use AsAction;

    /**
     * Set all partner revenue percentages to 0 and recalculate all affected bookings.
     *
     * @param bool $dryRun If true, shows what would be changed without making changes
     * @return array Statistics about the operation
     * @throws Throwable
     */
    public function handle(bool $dryRun = false): array
    {
        $stats = [
            'partners_found' => 0,
            'partners_with_non_zero_percentage' => 0,
            'partners_updated' => 0,
            'bookings_found' => 0,
            'bookings_recalculated' => 0,
            'errors' => [],
            'dry_run' => $dryRun,
        ];

        Log::info('Starting SetPartnerRevenueToZeroAndRecalculate', ['dry_run' => $dryRun]);

        // Step 1: Update partner percentages
        $this->updatePartnerPercentages($dryRun, $stats);

        // Step 2: Recalculate bookings with partner earnings
        $this->recalculateBookingsWithPartnerEarnings($dryRun, $stats);

        Log::info('Completed SetPartnerRevenueToZeroAndRecalculate', $stats);

        return $stats;
    }

    private function updatePartnerPercentages(bool $dryRun, array &$stats): void
    {
        // Find all partners
        $partners = Partner::all();
        $stats['partners_found'] = $partners->count();

        // Find partners with non-zero percentage
        $partnersToUpdate = $partners->where('percentage', '!=', 0);
        $stats['partners_with_non_zero_percentage'] = $partnersToUpdate->count();

        if (!$dryRun && $partnersToUpdate->isNotEmpty()) {
            // Update all partner percentages to 0 in a single query
            Partner::whereIn('id', $partnersToUpdate->pluck('id'))
                ->update(['percentage' => 0]);

            $stats['partners_updated'] = $partnersToUpdate->count();

            // Log the update
            activity()
                ->withProperties([
                    'partner_ids' => $partnersToUpdate->pluck('id')->toArray(),
                    'previous_percentages' => $partnersToUpdate->mapWithKeys(
                        fn($partner) => [$partner->id => $partner->percentage]
                    )->toArray(),
                ])
                ->log('Bulk updated partner percentages to 0');
        } elseif ($dryRun) {
            $stats['partners_updated'] = $partnersToUpdate->count();
        }
    }

    private function recalculateBookingsWithPartnerEarnings(bool $dryRun, array &$stats): void
    {
        // Find all confirmed bookings that have partner earnings
        $bookings = $this->getBookingsWithPartnerEarnings();
        $stats['bookings_found'] = $bookings->count();

        if ($bookings->isEmpty()) {
            return;
        }

        $calculationService = app(BookingCalculationService::class);

        // Process bookings in chunks to avoid memory issues
        $bookings->chunk(100)->each(function (Collection $bookingChunk) use ($dryRun, $calculationService, &$stats) {
            foreach ($bookingChunk as $booking) {
                $this->recalculateBooking($booking, $dryRun, $calculationService, $stats);
            }
        });
    }

    private function getBookingsWithPartnerEarnings(): Collection
    {
        return Booking::query()
            ->with(['venue.user', 'concierge.user', 'earnings'])
            ->whereIn('status', ['confirmed', 'venue_confirmed', 'partially_refunded'])
            ->where(function ($query) {
                $query->whereNotNull('partner_concierge_id')
                    ->orWhereNotNull('partner_venue_id')
                    ->orWhereHas('earnings', function ($earningsQuery) {
                        $earningsQuery->whereIn('type', ['partner_concierge', 'partner_venue']);
                    });
            })
            ->get();
    }

    private function recalculateBooking(Booking $booking, bool $dryRun, BookingCalculationService $calculationService, array &$stats): void
    {
        DB::beginTransaction();

        try {
            if (!$dryRun) {
                // Store original earnings for logging
                $originalEarnings = $booking->earnings()
                    ->whereIn('type', ['partner_concierge', 'partner_venue'])
                    ->get()
                    ->mapWithKeys(fn($earning) => [$earning->type => $earning->amount])
                    ->toArray();

                // Delete existing earnings
                $booking->earnings()->delete();

                // Recalculate earnings
                $calculationService->calculateEarnings($booking->refresh());

                // Get new earnings for comparison
                $newEarnings = $booking->earnings()
                    ->whereIn('type', ['partner_concierge', 'partner_venue'])
                    ->get()
                    ->mapWithKeys(fn($earning) => [$earning->type => $earning->amount])
                    ->toArray();

                // Log the recalculation
                activity()
                    ->performedOn($booking)
                    ->withProperties([
                        'action' => 'partner_revenue_zeroed_recalculation',
                        'original_partner_earnings' => $originalEarnings,
                        'new_partner_earnings' => $newEarnings,
                        'original_platform_earnings' => $booking->getOriginal('platform_earnings'),
                        'new_platform_earnings' => $booking->platform_earnings,
                    ])
                    ->log('Booking recalculated after partner revenue set to zero');
            }

            $stats['bookings_recalculated']++;
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            $error = [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];

            $stats['errors'][] = $error;

            Log::error('Failed to recalculate booking after partner revenue zero', $error);
        }
    }

    /**
     * Get a summary of what would be changed in dry-run mode
     */
    public function getDryRunSummary(): array
    {
        $partners = Partner::where('percentage', '!=', 0)->with('user')->get();
        $bookings = $this->getBookingsWithPartnerEarnings();

        return [
            'partners_to_update' => $partners->count(),
            'partner_details' => $partners->map(fn($partner) => [
                'id' => $partner->id,
                'current_percentage' => $partner->percentage,
                'company_name' => $partner->company_name,
                'user_name' => $partner->user->name ?? 'Unknown',
            ]),
            'bookings_to_recalculate' => $bookings->count(),
            'estimated_partner_earnings_to_zero' => $bookings->sum(function ($booking) {
                return $booking->earnings()
                    ->whereIn('type', ['partner_concierge', 'partner_venue'])
                    ->sum('amount');
            }),
        ];
    }
}

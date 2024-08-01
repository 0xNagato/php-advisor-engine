<?php

namespace App\Services\Booking;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class BookingCalculationService
{
    public function __construct(
        private PrimeEarningsCalculationService $primeEarningsCalculationService,
        private NonPrimeEarningsCalculationService $nonPrimeEarningsCalculationService
    ) {}

    /**
     * @throws Throwable
     */
    public function calculateEarnings(Booking $booking): void
    {
        Log::info('Starting earnings calculation for booking: '.$booking->id);

        DB::transaction(function () use ($booking) {
            if ($booking->is_prime) {
                $this->primeEarningsCalculationService->calculate($booking);
            } else {
                $this->nonPrimeEarningsCalculationService->calculate($booking);
            }
        });

        Log::info('Finished earnings calculation for booking: '.$booking->id);
    }

    public function calculateNonPrimeEarnings(Booking $booking): void
    {
        $this->nonPrimeEarningsCalculationService->calculate($booking);
    }
}

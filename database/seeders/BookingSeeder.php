<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleWithBookingMV;
use App\Models\Venue;
use App\Services\SalesTaxService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class BookingSeeder extends Seeder
{
    public const int BOOKINGS_COUNT = 200;

    public function run(): void
    {
        $concierges = Concierge::all();
        $salesTaxService = app(SalesTaxService::class);

        $startDate = now()->subDays(30);
        $endDate = now()->addDays(15);

        /**
         * @var Collection<Venue> $venues
         */
        $venues = Venue::with([
            'schedules' => function ($query) use ($startDate, $endDate) {
                $query->where('is_available', true)
                    ->whereBetween('booking_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->with('venue');
            }, 'inRegion',
        ])->get();

        foreach ($venues as $venue) {
            $availableSchedules = $venue->schedules->shuffle()->take(self::BOOKINGS_COUNT);

            foreach ($availableSchedules as $schedule) {
                $this->createBooking($venue, $schedule, $concierges, $salesTaxService);
            }
        }
    }

    private function createBooking(
        Venue $venue,
        ScheduleWithBookingMV $schedule,
        Collection $concierges,
        SalesTaxService $salesTaxService
    ): void {
        $bookingDate = Carbon::parse($schedule->booking_date)->setTimeFromTimeString($schedule->start_time);

        /**
         * @var Booking $booking
         */
        $booking = Booking::factory()->create([
            'schedule_template_id' => $schedule->schedule_template_id,
            'concierge_id' => $concierges->random()->id,
            'status' => BookingStatus::CONFIRMED,
            'booking_at' => $bookingDate,
            'guest_count' => $schedule->party_size,
            'created_at' => $bookingDate,
            'updated_at' => $bookingDate,
            'currency' => $venue->inRegion->currency,
            'is_prime' => true,
        ]);

        $taxData = $salesTaxService->calculateTax($venue->region, $booking->total_fee);
        $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

        $booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->region,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
            'confirmed_at' => $bookingDate,
        ]);
    }
}

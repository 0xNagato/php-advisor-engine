<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Restaurant;
use App\Models\ScheduleWithBooking;
use App\Services\SalesTaxService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class BookingSeeder extends Seeder
{
    public const int BOOKINGS_COUNT = 20;

    public function run(): void
    {
        $concierges = Concierge::all();
        $salesTaxService = app(SalesTaxService::class);

        $restaurants = Restaurant::with(['schedules' => function ($query) {
            $query->where('is_available', true)
                ->where('booking_date', now()->subDay()->format('Y-m-d'))
                ->with('restaurant');
        }, 'inRegion'])->get();

        foreach ($restaurants as $restaurant) {
            $availableSchedules = $restaurant->schedules->shuffle()->take(self::BOOKINGS_COUNT);

            foreach ($availableSchedules as $schedule) {
                $this->createBooking($restaurant, $schedule, $concierges, $salesTaxService);
            }
        }
    }

    private function createBooking(Restaurant $restaurant, ScheduleWithBooking $schedule, Collection $concierges, SalesTaxService $salesTaxService): void
    {
        /**
         * @var Booking $booking
         */
        $booking = Booking::factory()->create([
            'schedule_template_id' => $schedule->schedule_template_id,
            'concierge_id' => $concierges->random()->id,
            'status' => BookingStatus::CONFIRMED,
            'booking_at' => $schedule->booking_at,
            'guest_count' => $schedule->party_size,
            'created_at' => $schedule->booking_at,
            'updated_at' => $schedule->booking_at,
            'currency' => $restaurant->inRegion->currency,
        ]);

        $taxData = $salesTaxService->calculateTax($restaurant->region, $booking->total_fee, noTax: config('app.no_tax'));
        $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

        $booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->region,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
            'confirmed_at' => $schedule->booking_at,
        ]);
    }
}

<?php

/** @noinspection NullPointerExceptionInspection */

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
    public function run(): void
    {
        $concierges = Concierge::all();
        $restaurants = Restaurant::all();
        $bookingsCount = 300;

        foreach ($restaurants as $restaurant) {
            for ($i = 0; $i < $bookingsCount; $i++) {
                $schedule = ScheduleWithBooking::query()
                    ->where('restaurant_id', $restaurant->id)
                    ->where('booking_date', now()->subDay()->format('Y-m-d'))
                    ->inRandomOrder()
                    ->first();

                $this->createBooking($schedule, $concierges);
            }
        }
    }

    private function createBooking(ScheduleWithBooking $schedule, Collection $concierges): void
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
        ]);

        $taxData = app(SalesTaxService::class)->calculateTax('miami', $booking->total_fee);
        $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

        $booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->city,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
            'confirmed_at' => $schedule->booking_at,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Restaurant;
use App\Models\Schedule;
use App\Services\SalesTaxService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Random\RandomException;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws RandomException
     */
    public function run(): void
    {
        // Get all concierges
        $concierges = Concierge::all();
        $restaurants = Restaurant::all();

        // Define the bookings count for each month
        $bookingsCountLastMonth = 300;
        $bookingsCountMonthBeforeLast = 250;

        // For each schedule
        foreach ($restaurants as $restaurant) {
            $schedule = Schedule::available()->where('restaurant_id', $restaurant->id)->inRandomOrder()->first();

            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays(30);

            // For each booking to be created
            for ($i = 0; $i < $bookingsCountLastMonth; $i++) {
                $this->createBooking($schedule, $concierges, $startDate, $endDate);
            }

            $endDate = $startDate;
            $startDate = Carbon::parse($startDate)->subDays(60);

            for ($i = 0; $i < $bookingsCountMonthBeforeLast; $i++) {
                $this->createBooking($schedule, $concierges, $startDate, $endDate);
            }
        }
    }

    /**
     * @throws RandomException
     */
    private function createBooking(Schedule $schedule, Collection $concierges, Carbon $startDate, Carbon $endDate): void
    {
        // Generate a random date within the given date range
        $randomDate = Carbon::createFromTimestamp(rand(
            strtotime($startDate),
            strtotime($endDate)
        ));

        $bookingAt = Carbon::parse($randomDate->toDateString().' '.$schedule->start_time)->format('Y-m-d H:i:s');

        // Half of the time, set the guest_count to 2. The other half, set it to a random number between 3 and 8.
        $guestCount = random_int(0, 1) === 0 ? 2 : random_int(3, 8);

        // Create a new booking using a factory
        $booking = Booking::factory()->create([
            'schedule_id' => $schedule->id,
            'concierge_id' => $concierges->random()->id,
            'status' => BookingStatus::CONFIRMED,
            'booking_at' => $bookingAt,
            'guest_count' => $guestCount,
            'created_at' => $bookingAt,
            'updated_at' => $bookingAt,
        ]);

        $taxData = app(SalesTaxService::class)->calculateTax('miami', $booking->total_fee);
        $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

        $booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->city,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
            'confirmed_at' => $bookingAt,
        ]);
    }
}

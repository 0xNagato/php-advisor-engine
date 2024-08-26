<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleWithBooking;
use App\Models\Venue;
use App\Services\SalesTaxService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GenerateDemoBookings
{
    use AsAction;

    public string $commandSignature = 'demo:generate-bookings';

    public string $commandDescription = 'Generate demo bookings for all venues';

    protected const int BOOKINGS_PER_DAY = 5;

    protected const int DAYS_TO_GENERATE = 4; // Today + 3 future days

    public function handle(): string
    {
        $venues = Venue::with(['schedules' => function ($query) {
            $query->where('is_available', true)
                ->whereBetween('booking_date', [now()->format('Y-m-d'), now()->addDays(self::DAYS_TO_GENERATE - 1)->format('Y-m-d')])
                ->with('venue');
        }, 'inRegion'])->get();

        $concierges = Concierge::all();
        $salesTaxService = app(SalesTaxService::class);

        foreach ($venues as $venue) {
            $this->generateBookingsForVenue($venue, $concierges, $salesTaxService);
        }

        return 'Demo bookings generated successfully.';
    }

    public function asCommand(Command $command): int
    {
        $result = $this->handle();
        $command->info($result);

        return CommandAlias::SUCCESS;
    }

    protected function generateBookingsForVenue(Venue $venue, Collection $concierges, SalesTaxService $salesTaxService): void
    {
        $dateRange = collect(range(0, self::DAYS_TO_GENERATE - 1))->map(fn ($days) => now()->addDays($days));

        foreach ($dateRange as $date) {
            $availableSchedules = $venue->schedules
                ->where('booking_date', $date->format('Y-m-d'))
                ->shuffle()
                ->take(self::BOOKINGS_PER_DAY);

            foreach ($availableSchedules as $schedule) {
                $this->createBooking($venue, $schedule, $concierges->random(), $salesTaxService);
            }
        }
    }

    protected function createBooking(Venue $venue, ScheduleWithBooking $schedule, Concierge $concierge, SalesTaxService $salesTaxService): void
    {
        $bookingDate = Carbon::parse($schedule->booking_date)->setTimeFromTimeString($schedule->start_time);

        $booking = Booking::create([
            'schedule_template_id' => $schedule->schedule_template_id,
            'concierge_id' => $concierge->id,
            'status' => BookingStatus::CONFIRMED,
            'booking_at' => $bookingDate,
            'guest_count' => $schedule->party_size,
            'created_at' => $bookingDate,
            'updated_at' => $bookingDate,
            'currency' => $venue->inRegion->currency,
            'is_prime' => true,
            'uuid' => Str::uuid(),
            'guest_first_name' => fake()->firstName(),
            'guest_last_name' => fake()->lastName(),
            'guest_email' => fake()->safeEmail(),
            'guest_phone' => fake()->phoneNumber(),
            'total_fee' => $schedule->fee($schedule->party_size),
        ]);

        $taxData = $salesTaxService->calculateTax($venue->region, $booking->total_fee, noTax: config('app.no_tax'));
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

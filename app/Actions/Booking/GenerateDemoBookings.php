<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Region;
use App\Models\ScheduleWithBooking;
use App\Models\Venue;
use App\Services\SalesTaxService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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

    private array $fakeNames = ['John', 'Jane', 'Alice', 'Bob', 'Charlie', 'Diana', 'Edward', 'Fiona', 'George', 'Hannah'];

    private array $fakeEmails = ['john@example.com', 'jane@example.com', 'alice@example.com', 'bob@example.com', 'charlie@example.com'];

    private array $fakePhones = ['+11234567890', '+19876543210', '+15551234567', '+14159876543', '+17778889999'];

    /**
     * @throws Exception
     */
    public function handle(): string
    {
        try {
            Log::info('Starting demo bookings generation');

            $venues = Venue::all();
            Log::info('Fetched venues: '.$venues->count());

            $concierges = Concierge::all();
            Log::info('Fetched concierges: '.$concierges->count());

            $salesTaxService = app(SalesTaxService::class);

            $regions = Region::all()->keyBy('id');

            $startDate = now();
            $endDate = now()->addDays(self::DAYS_TO_GENERATE - 1);

            foreach ($venues as $venue) {
                Log::info('Generating bookings for venue: '.$venue->id);
                $this->generateBookingsForVenue($venue, $concierges, $salesTaxService, $regions, $startDate, $endDate);
            }

            return 'Demo bookings generated successfully.';
        } catch (Exception $e) {
            Log::error('Error generating demo bookings: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());
            throw $e;
        }
    }

    public function asCommand(Command $command): int
    {
        try {
            $result = $this->handle();
            $command->info($result);

            return CommandAlias::SUCCESS;
        } catch (Exception $e) {
            $command->error('Error generating demo bookings: '.$e->getMessage());
            $command->error('Check the Laravel log for more details.');

            return CommandAlias::FAILURE;
        }
    }

    /**
     * @param  Collection<Region>  $regions
     *
     * @throws Exception
     */
    protected function generateBookingsForVenue(Venue $venue, Collection $concierges, SalesTaxService $salesTaxService, Collection $regions, Carbon $startDate, Carbon $endDate): void
    {
        try {
            $dateRange = collect(range(0, self::DAYS_TO_GENERATE - 1))->map(fn ($days) => $startDate->copy()->addDays($days));

            foreach ($dateRange as $date) {
                $availableSchedules = ScheduleWithBooking::where('venue_id', $venue->id)
                    ->where('is_available', true)
                    ->where('booking_date', $date->format('Y-m-d'))
                    ->inRandomOrder()
                    ->take(self::BOOKINGS_PER_DAY)
                    ->get();

                foreach ($availableSchedules as $schedule) {
                    $this->createBooking($venue, $schedule, $concierges->random(), $salesTaxService, $regions);
                }
            }
        } catch (Exception $e) {
            Log::error("Error generating bookings for venue $venue->id: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * @param  Collection<Region>  $regions
     *
     * @throws Exception
     */
    protected function createBooking(Venue $venue, ScheduleWithBooking $schedule, Concierge $concierge, SalesTaxService $salesTaxService, Collection $regions): void
    {
        try {
            $bookingDate = Carbon::parse($schedule->booking_date)->setTimeFromTimeString($schedule->start_time);

            /** @var Region $region */
            $region = $regions[$venue->region];

            $booking = Booking::query()->create([
                'schedule_template_id' => $schedule->schedule_template_id,
                'concierge_id' => $concierge->id,
                'status' => BookingStatus::CONFIRMED,
                'booking_at' => $bookingDate,
                'guest_count' => $schedule->party_size,
                'created_at' => $bookingDate,
                'updated_at' => $bookingDate,
                'currency' => $region->currency,
                'is_prime' => true,
                'uuid' => Str::uuid(),
                'guest_first_name' => $this->fakeNames[array_rand($this->fakeNames)],
                'guest_last_name' => $this->fakeNames[array_rand($this->fakeNames)],
                'guest_email' => $this->fakeEmails[array_rand($this->fakeEmails)],
                'guest_phone' => $this->fakePhones[array_rand($this->fakePhones)],
                'total_fee' => $schedule->fee($schedule->party_size),
            ]);

            $taxData = $salesTaxService->calculateTax($region->id, $booking->total_fee, noTax: config('app.no_tax'));
            $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

            $booking->update([
                'tax' => $taxData->tax,
                'tax_amount_in_cents' => $taxData->amountInCents,
                'city' => $taxData->region,
                'total_with_tax_in_cents' => $totalWithTaxInCents,
                'confirmed_at' => $bookingDate,
            ]);
        } catch (Exception $e) {
            Log::error("Error creating booking for venue $venue->id, schedule $schedule->id: ".$e->getMessage());
            throw $e;
        }
    }
}

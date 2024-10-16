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
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class GenerateDemoBookings
{
    use AsAction;

    public string $commandSignature = 'demo:generate-bookings
                                       {--start-date= : Start date for booking generation (format: Y-m-d)}
                                       {--end-date= : End date for booking generation (format: Y-m-d)}
                                       {--days=4 : Number of days to generate bookings for}';

    public string $commandDescription = 'Generate demo bookings for all venues';

    protected const int BOOKINGS_PER_DAY = 5;

    private array $fakeNames = ['John', 'Jane', 'Alice', 'Bob', 'Charlie', 'Diana', 'Edward', 'Fiona', 'George', 'Hannah'];

    private array $fakeEmails = ['john@example.com', 'jane@example.com', 'alice@example.com', 'bob@example.com', 'charlie@example.com'];

    private array $fakePhones = ['+11234567890', '+19876543210', '+15551234567', '+14159876543', '+17778889999'];

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?Command $command = null): string
    {
        try {
            $startDateCarbon = $startDate ? Carbon::parse($startDate) : now();

            if ($endDate) {
                $endDateCarbon = Carbon::parse($endDate);
            } else {
                $endDateCarbon = $startDateCarbon->copy()->addDays($daysToGenerate - 1);
                $endDate = $endDateCarbon->format('Y-m-d'); // Convert back to string for consistency
            }

            if (! $startDate) {
                $startDate = $startDateCarbon->format('Y-m-d'); // Convert to string if it wasn't provided
            }

            $venues = Venue::all();
            $actualDays = $startDateCarbon->diffInDays($endDateCarbon) + 1; // +1 to include both start and end dates
            $totalPotentialBookings = $actualDays * self::BOOKINGS_PER_DAY * $venues->count();

            $summaryInfo = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_to_generate' => $actualDays,
                'bookings_per_day' => self::BOOKINGS_PER_DAY,
                'number_of_venues' => $venues->count(),
                'total_potential_bookings' => $totalPotentialBookings,
            ];

            Log::info('Starting demo bookings generation', $summaryInfo);

            if ($command) {
                $command->info('Starting demo bookings generation');
                $command->table(['Key', 'Value'], collect($summaryInfo)->map(fn ($value, $key) => [$key, $value])->toArray());
            }

            $concierges = Concierge::all();
            $salesTaxService = app(SalesTaxService::class);
            $regions = Region::all()->keyBy('id');

            $progressBar = null;
            if ($command) {
                $progressBar = $command->getOutput()->createProgressBar($totalPotentialBookings);
                $progressBar->start();
            }

            foreach ($venues as $venue) {
                $this->generateBookingsForVenue($venue, $concierges, $salesTaxService, $regions, $startDateCarbon, $endDateCarbon, $progressBar);
            }

            if ($progressBar) {
                $progressBar->finish();
                $command->getOutput()->newLine(2);
            }

            return 'Demo bookings generated successfully.';
        } catch (Exception $e) {
            Log::error('Error generating demo bookings: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function asCommand(Command $command): int
    {
        try {
            $startDate = $command->option('start-date');
            $endDate = $command->option('end-date');
            $daysToGenerate = (int) $command->option('days');
            $result = $this->handle($startDate, $endDate, $daysToGenerate, $command);
            $command->info($result);

            return CommandAlias::SUCCESS;
        } catch (Exception $e) {
            $command->error('Error generating demo bookings: '.$e->getMessage());
            $command->error('Check the Laravel log for more details.');

            return CommandAlias::FAILURE;
        }
    }

    /**
     * @throws Exception|Throwable
     */
    protected function generateBookingsForVenue(Venue $venue, Collection $concierges, SalesTaxService $salesTaxService, Collection $regions, Carbon $startDate, Carbon $endDate, ?ProgressBar $progressBar): void
    {
        try {
            $dateRange = collect($startDate->daysUntil($endDate));

            foreach ($dateRange as $date) {
                $availableSchedules = ScheduleWithBooking::query()->where('venue_id', $venue->id)
                    ->where('is_available', true)
                    ->where('booking_date', $date->format('Y-m-d'))
                    ->inRandomOrder()
                    ->take(self::BOOKINGS_PER_DAY)
                    ->get();

                foreach ($availableSchedules as $schedule) {
                    $this->createBooking($venue, $schedule, $concierges->random(), $salesTaxService, $regions);
                    $progressBar?->advance();
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
     * @throws Exception|Throwable
     */
    protected function createBooking(Venue $venue, ScheduleWithBooking $schedule, Concierge $concierge, SalesTaxService $salesTaxService, Collection $regions): void
    {
        $bookingDate = Carbon::parse($schedule->booking_date)->setTimeFromTimeString($schedule->start_time);

        $region = $regions->firstWhere('id', $venue->region);

        $guestCount = max(2, min(8, $schedule->party_size));

        $booking = Booking::query()->create([
            'schedule_template_id' => $schedule->schedule_template_id,
            'concierge_id' => $concierge->id,
            'status' => BookingStatus::PENDING,
            'booking_at' => $bookingDate,
            'guest_count' => $guestCount,
            'created_at' => now(),
            'updated_at' => now(),
            'currency' => $region->currency,
            'is_prime' => $schedule->prime_time,
            'guest_first_name' => $this->fakeNames[array_rand($this->fakeNames)],
            'guest_last_name' => $this->fakeNames[array_rand($this->fakeNames)],
            'guest_email' => $this->fakeEmails[array_rand($this->fakeEmails)],
            'guest_phone' => $this->fakePhones[array_rand($this->fakePhones)],
        ]);

        $taxData = $salesTaxService->calculateTax($region->id, $booking->total_fee, noTax: config('app.no_tax'));
        $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

        $booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->region,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
            'confirmed_at' => now(),
            'status' => BookingStatus::CONFIRMED,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function generateBookingsForConcierge(Concierge $concierge, Carbon $startDate, Carbon $endDate, int $count): void
    {
        $salesTaxService = new SalesTaxService;
        $regions = Region::all()->keyBy('id');
        $venues = Venue::query()->inRandomOrder()->take(5)->get();

        $dateRange = collect(range(0, $endDate->diffInDays($startDate)))
            ->map(fn ($day) => $startDate->copy()->addDays($day));

        $createdBookings = 0;

        foreach ($dateRange as $date) {
            foreach ($venues as $venue) {
                if ($createdBookings >= $count) {
                    break 2;
                }

                $availableSchedules = ScheduleWithBooking::query()
                    ->where('venue_id', $venue->id)
                    ->where('is_available', true)
                    ->where('booking_date', $date->format('Y-m-d'))
                    ->inRandomOrder()
                    ->take(1)
                    ->get();

                foreach ($availableSchedules as $schedule) {
                    try {
                        $this->createBooking($venue, $schedule, $concierge, $salesTaxService, $regions);
                        $createdBookings++;
                    } catch (Exception $e) {
                        Log::error('Error creating booking: '.$e->getMessage());
                    }

                    if ($createdBookings >= $count) {
                        break 3;
                    }
                }
            }
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\BookingModificationRequest;
use App\Models\ScheduleTemplate;
use App\Services\Booking\BookingCalculationService;
use Illuminate\Console\Command;

class FixBookingModificationScheduleTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prima:fix-schedule-templates
        {--recalculate : Recalculate earnings for affected bookings}
        {--dry-run : Show what would be updated without making any changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and fix booking modification requests where guest count changes require different schedule templates';

    /**
     * Mapping of guest counts to table sizes.
     *
     * @var array<int, int>
     */
    protected array $guestCountToTableSize = [
        2 => 2,
        3 => 4,
        4 => 4,
        5 => 6,
        6 => 6,
        7 => 8,
        8 => 8,
    ];

    public function __construct(
        protected BookingCalculationService $calculationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->info('DRY RUN: No changes will be made');
            $this->newLine();
        }

        $this->info('Starting to analyze approved booking modification requests...');

        // Find all approved requests where guest counts differ but schedule template remains the same
        $requests = BookingModificationRequest::query()
            ->whereColumn('original_guest_count', '!=', 'requested_guest_count')
            ->whereColumn('original_schedule_template_id', '=', 'requested_schedule_template_id')
            ->where('status', BookingModificationRequest::STATUS_APPROVED)
            ->with(['booking', 'booking.earnings', 'booking.venue', 'booking.concierge']) // Eager load relationships
            ->get();

        if ($requests->isEmpty()) {
            $this->info('No mismatched approved requests found.');

            return self::SUCCESS;
        }

        $this->info("Found {$requests->count()} approved requests with potential mismatches.");

        /** @var \Illuminate\Support\Collection<BookingModificationRequest> $updatedRequests */
        $updatedRequests = collect();

        /** @var BookingModificationRequest $request */
        foreach ($requests as $request) {
            if ($this->processRequest($request)) {
                $updatedRequests->push($request);
            }
        }

        if ($this->option('recalculate') && $updatedRequests->isNotEmpty()) {
            $this->info("Recalculating earnings for {$updatedRequests->count()} updated bookings...");

            foreach ($updatedRequests as $request) {
                if ($request->booking) {
                    $this->info("Booking ID: {$request->booking->id}");
                    $this->info("  - Original Guest Count: {$request->original_guest_count}");
                    $this->info("  - Requested Guest Count: {$request->requested_guest_count}");
                    $this->info("  - Current Schedule Template: {$request->booking->schedule_template_id}");
                    $this->newLine();

                    // Store current earnings for comparison
                    $currentEarnings = $request->booking->earnings->mapWithKeys(function ($earning) {
                        return [$earning->type => $earning->amount];
                    })->all();

                    if (! $this->option('dry-run')) {
                        // Delete existing earnings
                        $request->booking->earnings()->delete();

                        // Recalculate earnings
                        $this->calculationService->calculateEarnings($request->booking->refresh());

                        // Refresh the booking to get the new earnings
                        $request->booking->refresh();

                        // Get new earnings for comparison
                        $newEarnings = $request->booking->earnings->mapWithKeys(function ($earning) {
                            return [$earning->type => $earning->amount];
                        })->all();

                        $rows = collect($currentEarnings)->keys()
                            ->merge(collect($newEarnings)->keys())
                            ->unique()
                            ->sortBy(function ($type) {
                                // Custom sort order for earnings types
                                $order = [
                                    'partner_concierge' => 1,
                                    'partner_venue' => 2,
                                    'venue_paid' => 3,
                                    'concierge_bounty' => 4,
                                ];

                                return $order[$type] ?? 99;
                            })
                            ->map(function ($type) use ($currentEarnings, $newEarnings, $request) {
                                $oldAmount = $currentEarnings[$type] ?? 0;
                                $newAmount = $newEarnings[$type] ?? 0;

                                return [
                                    'Type' => $type,
                                    'Before' => money($oldAmount, $request->booking->venue->currency),
                                    'After' => money($newAmount, $request->booking->venue->currency),
                                ];
                            })
                            ->toArray();

                        $this->table(
                            ['Type', 'Before', 'After'],
                            $rows
                        );
                    } else {
                        $this->info('  - Would recalculate earnings after template update');
                    }

                    $this->newLine();
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('DRY RUN: No changes were made');
        } else {
            $this->info('Processing completed.');
        }

        return self::SUCCESS;
    }

    /**
     * Process a single modification request.
     */
    protected function processRequest(BookingModificationRequest $request): bool
    {
        $originalTemplate = ScheduleTemplate::find($request->original_schedule_template_id);

        if (! $originalTemplate) {
            $this->warn("Original schedule template not found for request ID: {$request->id}");

            return false;
        }

        // Get the required table size for the requested guest count
        $tableSize = $this->getTableSizeForGuestCount($request->requested_guest_count);

        if ($tableSize === null) {
            $this->warn("Invalid guest count for request ID: {$request->id} (Guest count: {$request->requested_guest_count} is outside supported range of 2-8)");

            return false;
        }

        // Find the correct template for the requested guest count
        $correctTemplate = ScheduleTemplate::query()
            ->where('venue_id', $originalTemplate->venue_id)
            ->where('day_of_week', $originalTemplate->day_of_week)
            ->where('start_time', $originalTemplate->start_time)
            ->where('party_size', $tableSize)
            ->where('is_available', true)
            ->first();

        if (! $correctTemplate) {
            $this->warn("No matching template found for request ID: {$request->id} (Guest count: {$request->requested_guest_count}, Table size: {$tableSize})");

            return false;
        }

        if ($correctTemplate->id === $request->requested_schedule_template_id) {
            $this->info("Request ID: {$request->id} already has correct template.");

            return false;
        }

        if (! $this->option('dry-run')) {
            // Update both the request and booking with the correct template
            $request->update(['requested_schedule_template_id' => $correctTemplate->id]);

            if ($request->booking) {
                $request->booking->update(['schedule_template_id' => $correctTemplate->id]);
            }
        }

        $prefix = $this->option('dry-run') ? 'Would update' : 'Updated';
        $this->info("{$prefix} request ID: {$request->id} - Changed template from {$request->original_schedule_template_id} to {$correctTemplate->id} for guest count {$request->requested_guest_count} (Table size: {$tableSize})");

        return true;
    }

    /**
     * Get the appropriate table size for a given guest count.
     */
    protected function getTableSizeForGuestCount(int $guestCount): ?int
    {
        return $this->guestCountToTableSize[$guestCount] ?? null;
    }
}

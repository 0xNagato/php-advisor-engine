<?php

namespace App\Console\Commands;

use App\Actions\Venue\UpdateVenueGroupEarnings;
use App\Enums\EarningType;
use App\Models\Earning;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignVenueGroupManagersToEarnings extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'prima:assign-venue-managers-to-earnings
                            {--dry-run=1 : Run without making changes (default)}
                            {--log : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns venue group primary managers to all venue earnings within their group';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $verbose = $this->option('verbose');

        // Summary counters
        $venueGroupsProcessed = 0;
        $venuesProcessed = 0;
        $earningsUpdated = 0;
        $earningsWithoutChange = 0;
        $errors = [];

        $this->info('Starting to process venue groups'.($dryRun ? ' (DRY RUN)' : ''));

        // Start a transaction for safety
        DB::beginTransaction();

        try {
            // Get all venue groups with primary managers
            $venueGroupsCount = VenueGroup::count();
            $this->info("Found {$venueGroupsCount} venue groups to process");

            $venueGroups = VenueGroup::with('primaryManager')->get();

            $progressBar = $this->output->createProgressBar(count($venueGroups));
            $progressBar->start();

            foreach ($venueGroups as $venueGroup) {
                // Check if venue group has a primary manager
                if (! $venueGroup->primaryManager) {
                    $errors[] = "Venue group {$venueGroup->id}: {$venueGroup->name} has no primary manager. Skipping.";
                    $progressBar->advance();

                    continue;
                }

                $primaryManager = $venueGroup->primaryManager;
                if ($verbose) {
                    $this->line("\nProcessing venue group {$venueGroup->id}: {$venueGroup->name} with primary manager {$primaryManager->id}: {$primaryManager->first_name} {$primaryManager->last_name}");
                }

                // Get all venues in this group
                $venues = Venue::where('venue_group_id', $venueGroup->id)->get();

                if ($venues->isEmpty()) {
                    $errors[] = "Venue group {$venueGroup->id}: {$venueGroup->name} has no venues. Skipping.";
                    $progressBar->advance();

                    continue;
                }

                // Process each venue to count them
                $venuesWithEarnings = 0;
                foreach ($venues as $venue) {
                    if ($verbose) {
                        $this->line("  Processing venue {$venue->id}: {$venue->name}");
                    }

                    // Get all bookings for this venue to check if it has any
                    $bookingCount = $venue->bookings()->count();

                    if ($bookingCount === 0) {
                        $errors[] = "    Venue {$venue->id}: {$venue->name} has no bookings. Skipping.";

                        continue;
                    }

                    // Check if venue has earnings of the types we're interested in
                    $earningTypes = [EarningType::VENUE->value, EarningType::VENUE_PAID->value];
                    $earningCount = $venue->bookings()
                        ->join('earnings', 'bookings.id', '=', 'earnings.booking_id')
                        ->whereIn('earnings.type', $earningTypes)
                        ->count();

                    if ($earningCount === 0) {
                        $errors[] = "    Venue {$venue->id}: {$venue->name} has no earnings of types ".implode(', ', $earningTypes).'. Skipping.';

                        continue;
                    }

                    $venuesWithEarnings++;
                }

                if ($venuesWithEarnings > 0) {
                    // Use our action to update earnings
                    if (! $dryRun) {
                        $venueEarningsUpdated = UpdateVenueGroupEarnings::run($venueGroup, $venues);
                        $earningsUpdated += $venueEarningsUpdated;
                    } else {
                        // In dry run mode, calculate how many would be updated
                        foreach ($venues as $venue) {
                            $bookingIds = $venue->bookings()->pluck('bookings.id')->toArray();

                            if (! empty($bookingIds)) {
                                $earningTypes = [EarningType::VENUE->value, EarningType::VENUE_PAID->value];
                                $venueEarnings = Earning::whereIn('type', $earningTypes)
                                    ->whereIn('booking_id', $bookingIds)
                                    ->get();

                                foreach ($venueEarnings as $earning) {
                                    if ($earning->user_id === $primaryManager->id) {
                                        $earningsWithoutChange++;
                                        if ($verbose) {
                                            $this->line("    Earning {$earning->id} already assigned to {$primaryManager->first_name} {$primaryManager->last_name}. Skipping.");
                                        }
                                    } else {
                                        $earningsUpdated++;

                                        // Get the old user info for logging
                                        $oldUserId = $earning->user_id;
                                        $oldUser = User::find($oldUserId);
                                        $oldUserName = $oldUser ? "{$oldUser->first_name} {$oldUser->last_name}" : "User ID: {$oldUserId}";

                                        if ($verbose) {
                                            $this->line("    Would update earning {$earning->id} from {$oldUserName} to {$primaryManager->first_name} {$primaryManager->last_name}");
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $venuesProcessed += $venuesWithEarnings;
                }

                $venueGroupsProcessed++;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Output summary
            $this->info('--------------------------------');
            $this->info('SUMMARY:');
            $this->info('--------------------------------');
            $this->info("Venue Groups Processed: {$venueGroupsProcessed}");
            $this->info("Venues Processed: {$venuesProcessed}");
            $this->info("Earnings Updated: {$earningsUpdated}");
            $this->info("Earnings Already Correct: {$earningsWithoutChange}");
            $this->info('Errors/Warnings: '.count($errors));

            if (count($errors) > 0) {
                $this->newLine();
                $this->warn('ERRORS/WARNINGS:');
                foreach ($errors as $index => $error) {
                    $this->warn(($index + 1).". {$error}");
                }
            }

            if ($dryRun) {
                $this->newLine();
                $this->warn('THIS WAS A DRY RUN. No actual changes were made.');
                DB::rollBack();
                $this->info('Transaction rolled back.');
            } else {
                DB::commit();
                $this->info('Transaction committed. All changes have been saved.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('ERROR: '.$e->getMessage());
            $this->error('Transaction rolled back. No changes were made.');
            $this->error('Error occurred at line: '.$e->getLine());
            $this->error('In file: '.$e->getFile());

            // Return failure
            return Command::FAILURE;
        }

        // Return success
        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Actions\Partner\SetPartnerRevenueToZeroAndRecalculate;
use Illuminate\Console\Command;
use Throwable;

class SetPartnerRevenueToZeroCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'prima:zero-partner-revenue
                            {--dry-run : Preview changes without making them}
                            {--force : Skip confirmation prompts}
                            {--summary : Show detailed summary of what would be changed}';

    /**
     * The console command description.
     */
    protected $description = 'Set all partner revenue percentages to 0% and recalculate all affected bookings';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');
        $showSummary = $this->option('summary');

        // Show header
        $this->info('🎯 PRIMA Partner Revenue Zero Operation');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made to the database');
        } else {
            $this->error('⚠️  LIVE MODE - Changes WILL be made to the database');
        }
        $this->newLine();

        // Show detailed summary if requested
        if ($showSummary || $isDryRun) {
            $this->showDetailedSummary();
        }

        // Get confirmation unless forced or dry-run
        if (!$isDryRun && !$force) {
            if (!$this->confirmOperation()) {
                $this->info('Operation cancelled by user.');
                return self::SUCCESS;
            }
        }

        // Execute the operation
        $this->info('🚀 Starting operation...');
        $this->newLine();

        try {
            $result = SetPartnerRevenueToZeroAndRecalculate::run($isDryRun);
            $this->displayResults($result);
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("❌ Operation failed: {$e->getMessage()}");
            $this->error("See logs for full details.");
            return self::FAILURE;
        }
    }

    private function showDetailedSummary(): void
    {
        $this->info('📊 Operation Summary:');
        $this->line('─────────────────────────────────────');

        try {
            $summary = app(SetPartnerRevenueToZeroAndRecalculate::class)->getDryRunSummary();

            $this->info("Partners to update: {$summary['partners_to_update']}");
            $this->info("Bookings to recalculate: {$summary['bookings_to_recalculate']}");
            $this->info("Estimated partner earnings to zero: " . money($summary['estimated_partner_earnings_to_zero'], 'USD'));

            if ($summary['partner_details']->isNotEmpty()) {
                $this->newLine();
                $this->info('🏢 Partners to be updated:');

                $tableData = $summary['partner_details']->map(fn($partner) => [
                    'ID' => $partner['id'],
                    'Company' => $partner['company_name'] ?: 'N/A',
                    'User' => $partner['user_name'],
                    'Current %' => $partner['current_percentage'] . '%',
                ])->toArray();

                $this->table(['ID', 'Company', 'User', 'Current %'], $tableData);
            }

            $this->newLine();
        } catch (Throwable $e) {
            $this->error("Could not generate summary: {$e->getMessage()}");
        }
    }

    private function confirmOperation(): bool
    {
        $this->warn('⚠️  This operation will:');
        $this->line('   • Set ALL partner revenue percentages to 0%');
        $this->line('   • Recalculate ALL bookings with partner earnings');
        $this->line('   • Move partner earnings to platform revenue');
        $this->line('   • Log all changes for audit purposes');
        $this->newLine();

        $this->error('🚨 This change is IRREVERSIBLE without manual intervention!');
        $this->newLine();

        return $this->confirm('Are you absolutely sure you want to proceed?', false);
    }

    private function displayResults(array $result): void
    {
        $this->newLine();

        if ($result['dry_run']) {
            $this->info('🔍 DRY RUN RESULTS:');
        } else {
            $this->info('✅ OPERATION COMPLETED:');
        }

        $this->line('─────────────────────────────────────');

        // Statistics table
        $statsTable = [
            ['Metric', 'Count'],
            ['Partners Found', $result['partners_found']],
            ['Partners with Non-Zero %', $result['partners_with_non_zero_percentage']],
            ['Partners Updated', $result['partners_updated']],
            ['Active Bookings Found', $result['bookings_found']],
            ['Inactive Bookings Found', $result['inactive_bookings_found'] ?? 0],
            ['Bookings Processed', $result['bookings_recalculated']],
            ['Errors', count($result['errors'])],
        ];

        $this->table(['Metric', 'Count'], array_slice($statsTable, 1));

        // Show errors if any
        if (!empty($result['errors'])) {
            $this->newLine();
            $this->error('❌ Errors encountered:');

            foreach ($result['errors'] as $error) {
                $this->line("   • Booking ID {$error['booking_id']}: {$error['error']}");
            }

            $this->line('');
            $this->warn('Check application logs for full error details.');
        }

        $this->newLine();

        if ($result['dry_run']) {
            $this->info('💡 Run without --dry-run to execute these changes.');
        } else {
            $this->info('🎉 Operation completed successfully!');

            if ($result['partners_updated'] > 0 || $result['bookings_recalculated'] > 0) {
                $this->info('📝 All changes have been logged in the activity log.');
            }
        }
    }
}

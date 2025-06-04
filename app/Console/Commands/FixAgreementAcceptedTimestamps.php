<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\VenueOnboarding;
use Exception;
use Illuminate\Console\Command;

class FixAgreementAcceptedTimestamps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:agreement-timestamps {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix venue onboardings where agreement_accepted is true but agreement_accepted_at is null';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        // Find records where agreement is accepted but timestamp is missing
        $problematicRecords = VenueOnboarding::query()
            ->where('agreement_accepted', true)
            ->whereNull('agreement_accepted_at')
            ->get();

        if ($problematicRecords->isEmpty()) {
            $this->info('No records found that need fixing.');

            return 0;
        }

        $this->info("Found {$problematicRecords->count()} records with missing agreement timestamps.");

        if ($isDryRun) {
            $this->warn('DRY RUN - No changes will be made');
            $this->table(
                ['ID', 'Company Name', 'Email', 'Created At', 'Agreement Accepted', 'Agreement Accepted At'],
                $problematicRecords->map(fn ($record) => [
                    $record->id,
                    $record->company_name,
                    $record->email,
                    $record->created_at->format('Y-m-d H:i:s'),
                    $record->agreement_accepted ? 'Yes' : 'No',
                    $record->agreement_accepted_at?->format('Y-m-d H:i:s') ?? 'NULL',
                ])
            );

            $this->info('Would set agreement_accepted_at to created_at timestamp for these records.');

            return 0;
        }

        // Confirm before making changes
        if (! $this->confirm('Do you want to set agreement_accepted_at to the created_at timestamp for these records?')) {
            $this->info('Operation cancelled.');

            return 0;
        }

        $updatedCount = 0;

        foreach ($problematicRecords as $record) {
            try {
                $record->update([
                    'agreement_accepted_at' => $record->created_at,
                ]);
                $updatedCount++;

                $this->line("✓ Updated record {$record->id} ({$record->company_name})");
            } catch (Exception $e) {
                $this->error("✗ Failed to update record {$record->id}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully updated {$updatedCount} out of {$problematicRecords->count()} records.");

        return 0;
    }
}

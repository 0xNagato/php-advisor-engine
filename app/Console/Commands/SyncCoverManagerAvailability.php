<?php

namespace App\Console\Commands;

use App\Models\Venue;
use App\Services\CoverManagerSyncReporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncCoverManagerAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-covermanager-availability {--venue-id= : Specific venue ID to sync} {--days=7 : Number of days to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync venue availability with CoverManager';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $venueId = $this->option('venue-id');
        $daysToSync = (int) $this->option('days');

        $query = Venue::query()
            ->whereHas('platforms', function (Builder $q) {
                $q->where('platform_type', 'covermanager')
                    ->where('is_enabled', true);
            });

        if ($venueId) {
            $query->where('id', $venueId);
        }

        $venues = $query->get();

        if ($venues->isEmpty()) {
            $this->info('No venues found for CoverManager sync.');

            return self::SUCCESS;
        }

        $this->info("Syncing {$venues->count()} venues with CoverManager for the next {$daysToSync} days.");

        $synced = 0;
        $failed = 0;

        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays($daysToSync);

        $progressBar = $this->output->createProgressBar($venues->count() * $daysToSync);
        $progressBar->start();

        foreach ($venues as $venue) {
            $this->line("\nSyncing venue: {$venue->name}");
            $venueStartTime = microtime(true);
            $venueDaysSuccessful = 0;
            $venueDaysFailed = 0;

            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                try {
                    $result = $venue->syncCoverManagerAvailability($currentDate);

                    if ($result) {
                        $synced++;
                        $venueDaysSuccessful++;
                    } else {
                        $failed++;
                        $venueDaysFailed++;
                        Log::error("Failed to sync venue {$venue->id} for date {$currentDate->format('Y-m-d')}");
                    }
                } catch (Throwable $e) {
                    $failed++;
                    $venueDaysFailed++;
                    Log::error("Exception syncing venue {$venue->id}", [
                        'error' => $e->getMessage(),
                        'date' => $currentDate->format('Y-m-d'),
                    ]);
                }

                $currentDate->addDay();
                $progressBar->advance();
            }
            
            // Show per-venue summary
            $venueElapsed = round(microtime(true) - $venueStartTime, 2);
            $venueTemplateCount = $venue->scheduleTemplates()->where('is_available', true)->count();
            $estimatedSlots = $venueTemplateCount * $daysToSync;
            
            if ($venueDaysFailed === 0) {
                $this->line("  ✓ {$daysToSync} days processed, ~{$estimatedSlots} slots analyzed ({$venueElapsed}s)");
            } else {
                $this->line("  ⚠ {$venueDaysSuccessful}/{$daysToSync} days successful, {$venueDaysFailed} failed ({$venueElapsed}s)");
            }
        }

        $progressBar->finish();

        // Generate comprehensive sync report
        $reporter = new CoverManagerSyncReporter();
        $report = $reporter->generateSyncReport($venues->toArray(), $startDate, $daysToSync);
        $reporter->displayReport($report, $this->output);

        // Traditional summary for backward compatibility
        $this->newLine();
        $this->info("Sync completed. Synced: {$synced}, Failed: {$failed}");
        
        // Log summary for external monitoring
        Log::info("SyncCoverManagerAvailability completed", [
            'synced' => $synced,
            'failed' => $failed,
            'venues_count' => $venues->count(),
            'days_synced' => $daysToSync,
            'overrides_created' => $report['overrides_created'],
            'overrides_removed' => $report['overrides_removed'],
            'total_slots_analyzed' => $report['total_slots_analyzed'],
            'override_rate' => $report['total_slots_analyzed'] > 0 ? round(($report['overrides_created'] / $report['total_slots_analyzed']) * 100, 2) : 0
        ]);

        return self::SUCCESS;
    }
}

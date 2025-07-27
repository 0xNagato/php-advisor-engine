<?php

namespace App\Console\Commands;

use App\Models\Venue;
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

            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                try {
                    $result = $venue->syncCoverManagerAvailability($currentDate);

                    if ($result) {
                        $synced++;
                    } else {
                        $failed++;
                        Log::error("Failed to sync venue {$venue->id} for date {$currentDate->format('Y-m-d')}");
                    }
                } catch (Throwable $e) {
                    $failed++;
                    Log::error("Exception syncing venue {$venue->id}", [
                        'error' => $e->getMessage(),
                        'date' => $currentDate->format('Y-m-d'),
                    ]);
                }

                $currentDate->addDay();
                $progressBar->advance();
            }
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info("Sync completed. Synced: {$synced}, Failed: {$failed}");

        return self::SUCCESS;
    }
}

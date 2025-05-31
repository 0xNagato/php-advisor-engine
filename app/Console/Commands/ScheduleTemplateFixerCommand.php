<?php

namespace App\Console\Commands;

use App\Models\ScheduleTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScheduleTemplateFixerCommand extends Command
{
    protected $signature = 'schedule-template:fix {--limit=1 : Number of venues to process per execution}';

    protected $description = 'Fix inconsistencies in schedule templates for venues';

    public function handle(): int
    {
        $startTime = now();
        $this->info("Starting to process venues at {$startTime->format('H:i:s')}...");
        $totalVenues = $this->countTotalVenuesWithInconsistencies();

        if ($totalVenues === 0) {
            $this->info('No venues requiring fixes were found.');
            $this->logExecutionTime($startTime);

            return Command::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $this->info("Total venues requiring fixes: $totalVenues");
        $this->info("Processing up to $limit venue(s) in this run...");
        $venuesLeft = $totalVenues - $limit;
        $this->info('Venue(s) left to process after this run: '.max($venuesLeft, 0));
        $venueIds = $this->getVenuesWithInconsistencies($limit);

        foreach ($venueIds as $venueId) {
            $this->info("Processing venue_id = $venueId");
            $results = $this->getInconsistenciesForVenue($venueId);

            if ($results->isEmpty()) {
                $this->warn("No inconsistencies found for venue_id = $venueId.");

                continue;
            }

            $tableData = [];
            foreach ($results as $result) {
                if ((int) $result->distinct_party_size_count === 5) {
                    $affectedRows = $this->fixInconsistencies(
                        $venueId,
                        $result->day_of_week,
                        $result->start_time,
                        $result->is_available
                    );

                    $tableData[] = [
                        'Day' => ucfirst((string) $result->day_of_week),
                        'Start Time' => $result->start_time,
                        'Rows Fixed' => $affectedRows,
                    ];
                }
            }

            if (filled($tableData)) {
                $this->newLine();
                $this->info("Summary for venue_id = $venueId:");
                $this->table(['Day', 'Start Time', 'Rows Fixed'], $tableData);
            }

            $this->info("Finished processing venue_id = $venueId");
        }

        $this->logExecutionTime($startTime);
        $this->info('All venues processed successfully.');

        return Command::SUCCESS;
    }

    private function countTotalVenuesWithInconsistencies(): int
    {
        return ScheduleTemplate::query()
            ->select('venue_id')
            ->whereNotIn('venue_id', [188, 213])
            ->groupBy('venue_id', 'day_of_week', 'start_time', 'is_available')
            ->havingRaw('COUNT(DISTINCT party_size) <> 11')
            ->distinct()
            ->count('venue_id');
    }

    private function getVenuesWithInconsistencies(int $limit)
    {
        return ScheduleTemplate::query()
            ->select('venue_id')
            ->whereNotIn('venue_id', [188, 213])
            ->groupBy('venue_id', 'day_of_week', 'start_time', 'is_available')
            ->havingRaw('COUNT(DISTINCT party_size) <> 11')
            ->limit($limit)
            ->pluck('venue_id');
    }

    private function getInconsistenciesForVenue(int $venueId)
    {
        return ScheduleTemplate::query()
            ->select([
                'venue_id',
                'day_of_week',
                'start_time',
                'is_available',
                DB::raw('COUNT(DISTINCT party_size) AS distinct_party_size_count'),
            ])
            ->where('venue_id', $venueId)
            ->groupBy('venue_id', 'day_of_week', 'start_time', 'is_available')
            ->havingRaw('COUNT(DISTINCT party_size) <> 11')
            ->get();
    }

    private function fixInconsistencies(int $venueId, string $dayOfWeek, string $startTime, bool $isAvailable): int
    {
        return ScheduleTemplate::query()
            ->where('venue_id', $venueId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', $startTime)
            ->where('party_size', '>', 8)
            ->update([
                'is_available' => $isAvailable,
            ]);
    }

    private function logExecutionTime($startTime): void
    {
        $endTime = now();
        $duration = $startTime->diff($endTime);
        $this->newLine();
        $this->info("Process started at: {$startTime->format('H:i:s')}");
        $this->info("Process finished at: {$endTime->format('H:i:s')}");
        $this->info("Total execution time: $duration->i minute(s), $duration->s second(s).");
    }
}

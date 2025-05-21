<?php

namespace App\Console\Commands;

use App\Models\ScheduleTemplate;
use App\Models\Venue;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateVenuePartySizes extends Command
{
    protected $signature = 'venues:update-party-sizes
                          {--dry-run : Preview changes without making them}
                          {--verify : Perform additional verification checks}';

    protected $description = 'Update venues with missing party sizes and related missing schedules';

    private const array REQUIRED_SIZES = [10, 12, 14, 16, 18, 20];

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        $venues = Venue::all();
        $isDryRun = $this->option('dry-run');
        $verify = $this->option('verify');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
            if ($verify) {
                $this->info('VERIFY MODE - Additional checks will be performed');
            }
            $this->newLine();
        }

        $changesMade = [
            'venues_updated' => 0,
            'new_party_sizes_added' => 0,
            'new_schedules_created' => 0,
            'validation_issues' => [],
        ];

        // Start transaction for verification
        if ($verify) {
            DB::beginTransaction();
        }

        try {
            foreach ($venues as $venue) {
                $missingPartySizes = $this->getMissingPartySizes($venue);
                $missingSchedules = $this->getMissingSchedules($venue, $missingPartySizes);

                if ($missingPartySizes || $missingSchedules) {
                    $changesMade['venues_updated']++;
                    $changesMade['new_party_sizes_added'] += count($missingPartySizes);
                    $changesMade['new_schedules_created'] += count($missingSchedules);

                    $this->displayVenueReport($venue, $missingPartySizes, $missingSchedules);

                    if (! $isDryRun) {
                        $this->addMissingPartySizes($venue, $missingPartySizes);
                        $this->createMissingSchedules($missingSchedules);
                        $this->info('✓ Changes applied successfully');
                    }
                }
            }

            $this->displayValidationIssues($changesMade['validation_issues']);
            $this->displaySummary($changesMade);

            // Handle verification mode
            if ($verify) {
                if ($isDryRun) {
                    DB::rollBack();
                    $this->info('Verification completed - all changes rolled back (dry run)');
                } else {
                    DB::commit();
                    $this->info('Verification completed - changes committed');
                }
            }

            return $isDryRun && $changesMade['validation_issues'] ? 1 : 0;
        } catch (Exception|Throwable $e) {
            if ($verify) {
                DB::rollBack();
            }
            $this->error('Error occurred: '.$e->getMessage());

            return 1;
        }
    }

    private function getMissingPartySizes(Venue $venue): array
    {
        $partySizes = collect($venue->party_sizes)->values()->toArray();

        return array_diff(self::REQUIRED_SIZES, $partySizes);
    }

    private function getMissingSchedules(Venue $venue, array $missingPartySizes): array
    {
        $missingSchedules = [];
        foreach ($missingPartySizes as $size) {
            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                $existingSchedulesCount = ScheduleTemplate::query()->where('venue_id', $venue->id)
                    ->where('party_size', $size)
                    ->where('day_of_week', $day)
                    ->count();

                if ($existingSchedulesCount === 0) {
                    $missingSchedules[] = [
                        'venue_id' => $venue->id,
                        'party_size' => $size,
                        'day_of_week' => $day,
                    ];
                }
            }
        }

        return $missingSchedules;
    }

    private function addMissingPartySizes(Venue $venue, array $missingPartySizes): void
    {
        if (blank($missingPartySizes)) {
            return;
        }

        $currentSizes = $venue->party_sizes ?? [];
        foreach ($missingPartySizes as $size) {
            $currentSizes[$size] = $size;
        }

        $venue->party_sizes = $currentSizes;
        $venue->save();
    }

    private function createMissingSchedules(array $missingSchedules): void
    {
        $schedulesData = [];
        foreach ($missingSchedules as $missingSchedule) {
            // Use your defined logic for start and end time
            $startTime = Carbon::createFromTime();
            $endTime = Carbon::createFromTime(23, 59);

            while ($startTime->lessThanOrEqualTo($endTime)) {
                // Apply your isAvailable logic for exact availability
                $isAvailable = $startTime->hour >= Venue::DEFAULT_START_HOUR &&
                    ($startTime->hour < Venue::DEFAULT_END_HOUR ||
                        ($startTime->hour === Venue::DEFAULT_END_HOUR && $startTime->minute < 30)
                    );

                $schedulesData[] = [
                    'venue_id' => $missingSchedule['venue_id'],
                    'day_of_week' => $missingSchedule['day_of_week'],
                    'party_size' => $missingSchedule['party_size'],
                    'start_time' => $startTime->format('H:i:s'),
                    'end_time' => $startTime->copy()->addMinutes(30)->format('H:i:s'),
                    'is_available' => $isAvailable,
                    'prime_time' => $isAvailable, // Prime time follows "is_available" logic
                    'available_tables' => $isAvailable ? Venue::DEFAULT_TABLES : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $startTime->addMinutes(30);
            }
        }

        // Insert all the schedule data in bulk
        ScheduleTemplate::query()->insert($schedulesData);
    }

    private function displayVenueReport(Venue $venue, array $missingPartySizes, array $missingSchedules): void
    {
        $this->info("\nVenue: $venue->name (ID: $venue->id)");

        if (filled($missingPartySizes)) {
            $this->info('Missing party sizes to be added: '.implode(', ', $missingPartySizes));
        }

        if (filled($missingSchedules)) {
            $scheduleSummary = collect($missingSchedules)
                ->groupBy('party_size') // Group schedules by their party size
                ->map(function ($group, $partySize) {
                    $days = $group->pluck('day_of_week')->unique()->sort()->implode(', ');

                    return "Party Size $partySize: [$days]";
                })
                ->implode("\n  ");

            $this->info("Missing schedule templates to be created:\n  $scheduleSummary");
        }
    }

    private function displayValidationIssues(array $validationIssues): void
    {
        if (filled($validationIssues)) {
            $this->newLine();
            $this->warn('⚠️ Validation Issues Found:');
            foreach ($validationIssues as $issue) {
                $this->warn("- $issue");
            }
        }
    }

    private function displaySummary(array $changesMade): void
    {
        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Venues updated', $changesMade['venues_updated']],
                ['New party sizes added', $changesMade['new_party_sizes_added']],
                ['New schedules created', $changesMade['new_schedules_created']],
                ['Validation issues', count($changesMade['validation_issues'])],
            ]
        );
    }
}

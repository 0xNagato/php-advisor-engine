<?php

namespace App\Console\Commands;

use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateScheduleTemplates extends Command
{
    protected $signature = 'venues:cleanup-schedules';

    protected $description = 'Remove duplicate schedule templates for venues';

    public function handle(): void
    {
        $venues = Venue::query()->get();
        $bar = $this->output->createProgressBar($venues->count());
        $bar->start();

        foreach ($venues as $venue) {
            DB::transaction(function () use ($venue) {
                // Find duplicates based on venue_id, day_of_week, start_time, and party_size
                $duplicates = DB::table('schedule_templates')
                    ->select('venue_id', 'day_of_week', 'start_time', 'party_size', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as keep_id'))
                    ->where('venue_id', $venue->id)
                    ->groupBy('venue_id', 'day_of_week', 'start_time', 'party_size')
                    ->having('count', '>', 1)
                    ->get();

                foreach ($duplicates as $duplicate) {
                    // Delete all duplicates except the one with the lowest ID
                    DB::table('schedule_templates')
                        ->where('venue_id', $duplicate->venue_id)
                        ->where('day_of_week', $duplicate->day_of_week)
                        ->where('start_time', $duplicate->start_time)
                        ->where('party_size', $duplicate->party_size)
                        ->where('id', '!=', $duplicate->keep_id)
                        ->delete();
                }
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Schedule templates cleanup completed!');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixVenuePrimeTimeConsistency extends Command
{
    protected $signature = 'venues:fix-prime-time {--venue-id= : Optional venue ID to process}';

    protected $description = 'Fix prime time consistency across party sizes for venues';

    public function handle()
    {
        if ($venueId = $this->option('venue-id')) {
            $venues = Venue::where('id', $venueId)->get();
        } else {
            $venues = Venue::all();
        }

        $this->info("Found {$venues->count()} venues to process");

        foreach ($venues as $venue) {
            $this->info("Processing venue: {$venue->name} (ID: {$venue->id})");

            // Get all unique day/time combinations using party_size = 0 as reference
            $templates = DB::table('schedule_templates')
                ->select('day_of_week', 'start_time')
                ->where('venue_id', $venue->id)
                ->where('party_size', 0)
                ->get();

            $bar = $this->output->createProgressBar($templates->count());

            foreach ($templates as $template) {
                // Get prime_time value from party_size = 0 (reference)
                $primeTimeValue = DB::table('schedule_templates')
                    ->where('venue_id', $venue->id)
                    ->where('day_of_week', $template->day_of_week)
                    ->where('start_time', $template->start_time)
                    ->where('party_size', 0)
                    ->value('prime_time');

                // Update all other party sizes for this time slot
                $updated = DB::table('schedule_templates')
                    ->where('venue_id', $venue->id)
                    ->where('day_of_week', $template->day_of_week)
                    ->where('start_time', $template->start_time)
                    ->where('party_size', '>', 0)
                    ->update(['prime_time' => $primeTimeValue]);

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Completed processing venue {$venue->id}");
        }

        $this->info('Prime time consistency fix completed successfully!');
    }
}

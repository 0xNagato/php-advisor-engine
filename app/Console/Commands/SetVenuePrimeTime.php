<?php

namespace App\Console\Commands;

use App\Enums\VenueStatus;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetVenuePrimeTime extends Command
{
    protected $signature = 'venues:set-prime-time
                            {--start=19 : Start hour for prime time (24-hour format)}
                            {--end=21 : End hour for prime time (24-hour format)}
                            {--end-minutes=30 : End minutes for prime time}
                            {--venues= : Comma-separated list of venue IDs (default: all venues)}
                            {--status=active : Status to set for the venues (active/pending/suspended)}';

    protected $description = 'Set prime time and status for venues';

    public function handle(): void
    {
        $startHour = (int) $this->option('start');
        $endHour = (int) $this->option('end');
        $endMinutes = (int) $this->option('end-minutes');
        $venueIds = $this->option('venues');
        $status = $this->option('status');

        if (! in_array($status, ['active', 'pending', 'suspended'])) {
            $this->error('Invalid status. Must be active, pending, or suspended.');

            return;
        }

        $query = Venue::query();
        if ($venueIds) {
            $query->whereIn('id', explode(',', $venueIds));
        }

        $venues = $query->get();

        $this->info('Updating prime time and status for '.$venues->count().' venues...');

        $venues->each(function ($venue) use ($startHour, $endHour, $endMinutes, $status) {
            $this->updateVenuePrimeTimeAndStatus($venue, $startHour, $endHour, $endMinutes, $status);
        });

        $this->info('Prime time and status update completed.');
    }

    private function updateVenuePrimeTimeAndStatus(Venue $venue, int $startHour, int $endHour, int $endMinutes, string $status): void
    {
        DB::transaction(function () use ($venue, $startHour, $endHour, $endMinutes, $status) {
            $endTime = sprintf('%02d:%02d:00', $endHour, $endMinutes);

            $venue->scheduleTemplates()
                ->update([
                    'prime_time' => DB::raw("TIME(start_time) >= '$startHour:00:00' AND TIME(start_time) <= '$endTime'"),
                ]);

            $venue->update(['status' => VenueStatus::from($status)]);

            $this->info("Updated prime time and status for venue: $venue->name (ID: $venue->id)");
        });
    }
}

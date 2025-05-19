<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class FixIbizaBookingCurrency extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:ibiza-bookings-currency {--dry-run : Preview changes without updating.}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Fix bookings in Ibiza with incorrect currency.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info('Processing Ibiza bookings with incorrect currency...');

        // Query bookings with USD currency and Ibiza region
        $bookings = Booking::query()->where('currency', 'USD')
            ->whereHas('venue', function ($query) {
                $query->where('region', 'ibiza');
            })
            ->with(['venue.inRegion'])
            ->orderByDesc('id')
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings found with USD currency in Ibiza.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info('Dry-run mode: Displaying affected bookings:');
        } else {
            $this->info('Updating bookings...');
        }

        $updatedCount = 0;

        foreach ($bookings as $booking) {
            $venueRegionCurrency = $booking->venue->inRegion->currency;

            if ($venueRegionCurrency && $booking->currency !== $venueRegionCurrency) {
                if ($dryRun) {
                    $this->line(
                        "Booking ID: $booking->id | Current: $booking->currency | New: $venueRegionCurrency"
                    );
                } else {
                    $booking->update(['currency' => $venueRegionCurrency]);
                    $updatedCount++;
                    $this->info("Updated Booking ID $booking->id to $venueRegionCurrency");
                }
            }
        }

        if ($dryRun) {
            $this->info('Dry-run complete. No changes made.');
        } else {
            $this->info("Finished updating $updatedCount bookings.");
        }

        return Command::SUCCESS;
    }
}

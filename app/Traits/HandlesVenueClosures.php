<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

trait HandlesVenueClosures
{
    /**
     * Dates when all venues are closed (format: MM-DD)
     */
    private const CLOSED_DATES = [
        '12-25', // Christmas Day
        '12-31', // New Year's Eve
        '02-14', // Valentine's Day
    ];

    /**
     * Apply closure rules to venue schedules
     */
    protected function applyClosureRules(Collection $venues, string|Carbon $date): Collection
    {
        if (! $this->isClosedDate($date)) {
            return $venues;
        }

        $closureVenues = $this->getClosureVenues();

        // If no specific venues are configured, apply to all venues
        $applyToAll = blank($closureVenues);

        $venues->each(function ($venue) use ($closureVenues, $applyToAll) {
            // Check both ID and slug to support both formats
            if ($applyToAll || in_array((string) $venue->id, $closureVenues, true) || in_array($venue->slug, $closureVenues, true)) {
                $venue->schedules->each(function ($schedule) {
                    if ($schedule->is_available) {
                        // If the venue was going to be open, mark as sold out
                        $schedule->is_available = true;
                        $schedule->remaining_tables = 0;
                        $schedule->is_bookable = false;
                    } else {
                        // If the venue was already closed, keep it closed
                        $schedule->is_available = false;
                        $schedule->remaining_tables = 0;
                        $schedule->is_bookable = false;
                    }
                });
            }
        });

        return $venues;
    }

    protected function isClosedDate(string|Carbon $date): bool
    {
        return in_array(Carbon::parse($date)->format('m-d'), self::CLOSED_DATES, true);
    }

    protected function getOverrideVenues(): array
    {
        $envOverrides = config('app.override_venues');

        return array_filter(explode(',', (string) $envOverrides));
    }

    protected function getClosureVenues(): array
    {
        $envClosures = config('app.closure_venues');

        // If numeric values are provided, convert them to strings for comparison
        return array_map('strval', array_filter(explode(',', (string) $envClosures)));
    }

    /**
     * Apply closure rules to a single venue's schedules
     */
    protected function applySingleVenueClosureRules(
        Collection $schedules,
        string|Carbon $date,
        string $venueSlug
    ): Collection {
        if (! $this->isClosedDate($date)) {
            return $schedules;
        }

        $closureVenues = $this->getClosureVenues();

        // If no specific venues are configured, apply to all venues
        $applyToAll = blank($closureVenues);

        if ($applyToAll || in_array($venueSlug, $closureVenues, true)) {
            $schedules->each(function ($schedule) {
                if ($schedule->is_available) {
                    // If the venue was going to be open, mark as sold out
                    $schedule->is_available = true;
                    $schedule->remaining_tables = 0;
                    $schedule->is_bookable = false;
                } else {
                    // If the venue was already closed, keep it closed
                    $schedule->is_available = false;
                    $schedule->remaining_tables = 0;
                    $schedule->is_bookable = false;
                }
            });
        }

        return $schedules;
    }
}

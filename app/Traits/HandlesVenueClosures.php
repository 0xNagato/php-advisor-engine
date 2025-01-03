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
    ];

    /**
     * Apply closure rules to venue schedules
     */
    protected function applyClosureRules(Collection $venues, string|Carbon $date): Collection
    {
        if (! $this->isClosedDate($date)) {
            return $venues;
        }

        $overrideVenues = $this->getOverrideVenues();

        $venues->each(function ($venue) use ($overrideVenues) {
            if (! in_array($venue->slug, $overrideVenues, true)) {
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

        $overrideVenues = $this->getOverrideVenues();

        if (! in_array($venueSlug, $overrideVenues, true)) {
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

<?php

namespace App\View\Components;

use App\Enums\VenueStatus;
use App\Models\Booking;
use App\Models\Venue;
use DB;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class VenueLogosScroll extends Component
{
    public Collection $firstRow;

    public Collection $secondRow;

    /**
     * Array of venue IDs to exclude from results
     */
    private array $excludedVenueIds = [73, 74, 71, 72];

    private array $amountByRegion = [
        'miami' => 20,
        'los_angeles' => 10,
        'ibiza' => 10,
    ];

    public function __construct()
    {
        $venues = cache()->remember('top_venues_scroll_latest_march_21', now()->addHours(24), fn () => $this->getTopVenues());

        // Randomize and split venues equally between first and second row
        $totalVenues = collect($venues)->flatten()->shuffle();
        $halfCount = (int) ceil($totalVenues->count() / 2);

        $this->firstRow = $totalVenues->take($halfCount);
        $this->secondRow = $totalVenues->skip($halfCount);
    }

    private function getTopVenues(): Collection
    {
        $allVenues = collect();

        foreach ($this->amountByRegion as $region => $amount) {
            $bookedVenues = Booking::query()
                ->select('venues.id', 'venues.name', 'venues.logo_path')
                ->join('schedule_templates', 'bookings.schedule_template_id', '=', 'schedule_templates.id')
                ->join('venues', 'schedule_templates.venue_id', '=', 'venues.id')
                ->whereNotNull('bookings.confirmed_at')
                ->where('bookings.created_at', '>=', now()->subDays(30))
                ->where('venues.status', VenueStatus::ACTIVE)
                ->where('venues.region', $region)
                ->whereNotIn('venues.id', $this->excludedVenueIds)
                ->groupBy('venues.id', 'venues.name', 'venues.logo_path')
                ->orderByDesc(DB::raw('COUNT(bookings.id)'))
                ->limit($amount)
                ->get();

            if ($bookedVenues->count() < $amount) {
                // Get IDs of venues we already have
                $existingIds = $bookedVenues->pluck('id');

                // Combine existing IDs with excluded IDs
                $idsToExclude = $existingIds->merge($this->excludedVenueIds)->unique();

                // Get additional random active venues for this region
                $additionalVenues = Venue::query()
                    ->select('id', 'name', 'logo_path')
                    ->where('status', VenueStatus::ACTIVE)
                    ->where('region', $region)
                    ->whereNotNull('logo_path')
                    ->whereNotIn('id', $idsToExclude)
                    ->inRandomOrder()
                    ->limit($amount - $bookedVenues->count())
                    ->get();

                $bookedVenues = $bookedVenues->concat($additionalVenues);
            }

            $allVenues = $allVenues->concat($bookedVenues);
        }

        return $allVenues;
    }

    public function render()
    {
        return view('components.venue-logos-scroll');
    }
}

<?php

namespace App\View\Components;

use App\Models\Booking;
use App\Models\Venue;
use DB;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class VenueLogosScroll extends Component
{
    public Collection $firstRow;

    public Collection $secondRow;

    public function __construct()
    {
        $venues = $this->getTopVenues();
        $this->firstRow = $venues->take(10);
        $this->secondRow = $venues->skip(10)->take(10);
    }

    private function getTopVenues(): Collection
    {
        $bookedVenues = Booking::query()
            ->select('venues.id', 'venues.name', 'venues.logo_path')
            ->join('schedule_templates', 'bookings.schedule_template_id', '=', 'schedule_templates.id')
            ->join('venues', 'schedule_templates.venue_id', '=', 'venues.id')
            ->whereNotNull('bookings.confirmed_at')
            ->where('bookings.created_at', '>=', now()->subDays(30))
            ->groupBy('venues.id', 'venues.name', 'venues.logo_path')
            ->orderByDesc(DB::raw('COUNT(bookings.id)'))
            ->limit(20)
            ->get();

        if ($bookedVenues->count() < 20) {
            // Get IDs of venues we already have
            $existingIds = $bookedVenues->pluck('id');

            // Get additional random venues excluding the ones we already have
            $additionalVenues = Venue::query()
                ->select('id', 'name', 'logo_path')
                ->whereNotNull('logo_path')
                ->whereNotIn('id', $existingIds)
                ->inRandomOrder()
                ->limit(20 - $bookedVenues->count())
                ->get();

            // Merge the collections
            return $bookedVenues->concat($additionalVenues);
        }

        return $bookedVenues;
    }

    public function render()
    {
        return view('components.venue-logos-scroll');
    }
}

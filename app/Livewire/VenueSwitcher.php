<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Venue;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use Livewire\Component;

class VenueSwitcher extends Component
{
    /** @var Collection<int, Venue>|\Illuminate\Support\Collection */
    public Collection $venues;

    public ?Venue $currentVenue = null;

    public function mount(): void
    {
        if (! auth()->user()?->hasActiveRole('venue_manager')) {
            return;
        }

        $venueGroup = auth()->user()->currentVenueGroup();
        if (! $venueGroup) {
            return;
        }

        $allowedVenueIds = $venueGroup->getAllowedVenueIds(auth()->user());

        // Return early if there are no allowed venues
        if (blank($allowedVenueIds)) {
            $this->venues = new Collection;

            return;
        }

        $this->venues = $venueGroup->venues()
            ->whereIn('id', $allowedVenueIds)
            ->get();
        $this->currentVenue = auth()->user()->currentVenueGroup()?->currentVenue(auth()->user());
        if (session()->has('impersonate.venue_id')) {
            $this->currentVenue = Venue::query()->find(session()->get('impersonate.venue_id'));
        }
    }

    public function switchVenue(int $venueId): void
    {
        if (! auth()->user()?->hasActiveRole('venue_manager')) {
            return;
        }

        $venueGroup = auth()->user()->currentVenueGroup();
        if (! $venueGroup) {
            return;
        }

        $venue = Venue::query()->find($venueId);
        if (! $venue) {
            return;
        }

        try {
            if (session()->has('impersonate.venue_id')) {
                session()->put('impersonate.venue_id', $venueId);
            } else {
                $venueGroup->switchVenue(auth()->user(), $venue);
            }
            $this->dispatch('venue-switched', venue: $venue);

            redirect()->to(request()->header('Referer'));
        } catch (InvalidArgumentException) {
            // Handle unauthorized venue access
            return;
        }
    }

    public function render(): View|Application|Factory
    {
        return view('livewire.venue-switcher');
    }
}

<?php

namespace App\Filament\Pages\Venue;

use App\Models\User;
use App\Models\Venue;
use Filament\Pages\Page;

class VenueSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.venue.venue-settings';

    public Venue $venue;

    public function getHeading(): string
    {
        return "{$this->venue->name} Settings";
    }

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasActiveRole('venue_manager')) {
            $currentVenue = auth()->user()?->currentVenueGroup()?->currentVenue(auth()->user());
            if (session()->has('impersonate.venue_id')) {
                $currentVenue = Venue::query()->find(session()->get('impersonate.venue_id'));
            }

            return $currentVenue?->name ?? 'Venue Management';
        }

        return null;
    }

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasActiveRole('venue')) {
            return true;
        }

        if ($user->hasActiveRole('venue_manager')) {
            $venueGroup = $user->currentVenueGroup();

            return filled($venueGroup?->getAllowedVenueIds($user));
        }

        return false;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->hasActiveRole(['venue', 'venue_manager']), 403);

        if (session()->has('impersonate.venue_id')) {
            $this->venue = Venue::query()->find(session()->get('impersonate.venue_id'));
        } elseif (auth()->user()->hasActiveRole('venue')) {
            $this->venue = auth()->user()->venue;
        } elseif (auth()->user()->hasActiveRole('venue_manager')) {
            $venueGroup = auth()->user()->currentVenueGroup();
            $currentVenue = $venueGroup?->currentVenue(auth()->user());

            abort_unless((bool) $currentVenue, 404, 'No active venue selected');
            $this->venue = $currentVenue;
        } else {
            abort(403, 'You are not authorized to access this page');
        }
    }
}

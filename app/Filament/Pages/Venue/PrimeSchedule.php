<?php

namespace App\Filament\Pages\Venue;

use App\Models\User;
use App\Models\Venue;
use Filament\Pages\Page;

class PrimeSchedule extends Page
{
    public const int DAYS_TO_DISPLAY = 30;

    protected static ?string $navigationIcon = 'polaris-calendar-time-icon';

    protected static ?int $navigationSort = 21;

    protected static string $view = 'filament.pages.venue.prime-schedule';

    protected static bool $shouldRegisterNavigation = false;

    public Venue $venue;

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasActiveRole('venue_manager')) {
            $currentVenue = auth()->user()?->currentVenueGroup()?->currentVenue(auth()->user());

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

        if (auth()->user()->hasActiveRole('venue')) {
            $this->venue = auth()->user()->venue;
        } elseif (auth()->user()->hasActiveRole('venue_manager')) {
            $venueGroup = auth()->user()->currentVenueGroup();
            $currentVenue = $venueGroup?->currentVenue(auth()->user());

            abort_unless($currentVenue, 404, 'No active venue selected');
            $this->venue = $currentVenue;
        } else {
            abort(403, 'You are not authorized to access this page');
        }
    }
}

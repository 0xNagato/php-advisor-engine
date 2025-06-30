<?php

namespace App\Filament\Pages\Venue;

use App\Models\User;
use App\Models\Venue;
use Filament\Pages\Page;

class ScheduleManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 22;

    protected static string $view = 'filament.pages.venue.schedule-manager';

    public Venue $venue;

    public function getHeading(): string
    {
        return "Schedule Manager: {$this->venue->name}";
    }

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasActiveRole('venue_manager')) {
            /** @var User $user */
            $user = auth()->user();
            $currentVenue = $user->currentVenueGroup()?->currentVenue($user);
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
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasActiveRole(['venue', 'venue_manager']), 403);

        if (session()->has('impersonate.venue_id')) {
            $this->venue = Venue::query()->find(session()->get('impersonate.venue_id'));
        } elseif ($user->hasActiveRole('venue')) {
            $this->venue = $user->venue;
        } elseif ($user->hasActiveRole('venue_manager')) {
            $venueGroup = $user->currentVenueGroup();
            $currentVenue = $venueGroup?->currentVenue($user);

            abort_unless((bool) $currentVenue, 404, 'No active venue selected');
            $this->venue = $currentVenue;
        } else {
            abort(403, 'You are not authorized to access this page');
        }
        $this->venue->load('scheduleTemplates');
    }
}

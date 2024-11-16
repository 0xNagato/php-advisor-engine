<?php

namespace App\Filament\Pages\Venue;

use App\Models\Venue;
use Filament\Pages\Page;

class VenueSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.venue.venue-settings';

    public Venue $venue;

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('venue');
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->hasActiveRole('venue'), 403);

        $this->venue = auth()->user()->venue;
    }
}

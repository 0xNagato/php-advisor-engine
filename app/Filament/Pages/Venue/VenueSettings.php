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
        return auth()->user()->hasRole('venue');
    }

    public function mount(): void
    {
        $this->venue = auth()->user()->venue;
    }
}

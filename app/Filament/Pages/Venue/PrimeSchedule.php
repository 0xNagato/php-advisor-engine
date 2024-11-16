<?php

namespace App\Filament\Pages\Venue;

use Filament\Pages\Page;

class PrimeSchedule extends Page
{
    public const int DAYS_TO_DISPLAY = 30;

    protected static ?string $navigationIcon = 'polaris-calendar-time-icon';

    protected static ?int $navigationSort = 21;

    protected static string $view = 'filament.pages.venue.prime-schedule';

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('venue');
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->hasActiveRole('venue'), 403);
    }
}

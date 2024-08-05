<?php

namespace App\Filament\Pages\Venue;

use App\Livewire\Venue\VenueRecentBookings;
use App\Livewire\Venue\VenueStats;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

class VenueDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $title = 'Earnings Snapshot';

    protected static string $routePath = 'venue';

    protected ?string $heading = 'Earnings Snapshot';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('venue');
    }

    public function mount(): void
    {
        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Date Range')
                ->icon('heroicon-o-calendar')
                ->iconButton()
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                    // ...
                ]),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VenueStats::make(['venue' => auth()->user()->venue, 'columnSpan' => 'full']),
            VenueRecentBookings::make(['venue' => auth()->user()->venue, 'columnSpan' => 'full']),
            // VenueLeaderboard::make(['venue' => auth()->user()->venue, 'columnSpan' => '1']),
        ];
    }
}

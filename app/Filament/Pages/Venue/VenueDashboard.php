<?php

namespace App\Filament\Pages\Venue;

use App\Filament\DateRangeFilterAction;
use App\Livewire\DateRangeFilterWidget;
use App\Livewire\Venue\VenueRecentBookings;
use App\Livewire\VenueOverview;
use Carbon\Carbon;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class VenueDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.venue.venue-dashboard';

    protected static ?string $title = 'Earnings Snapshot';

    protected static string $routePath = 'venue';

    protected ?string $heading = 'Earnings Snapshot';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasActiveRole('venue');
    }

    public function mount(): void
    {
        $this->filters['startDate'] ??= now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] ??= now()->format('Y-m-d');
    }

    public function getSubheading(): string|null|Htmlable
    {
        if (! isset($this->filters['startDate'], $this->filters['endDate'])) {
            return null; // or return a default value like 'N/A' or an empty string
        }

        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);

        $formattedStartDate = $startDate->format('M j');
        $formattedEndDate = $endDate->format('M j');

        return $formattedStartDate.' - '.$formattedEndDate;
    }

    protected function getHeaderActions(): array
    {
        return [
            DateRangeFilterAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DateRangeFilterWidget::make([
                'startDate' => $this->filters['startDate'],
                'endDate' => $this->filters['endDate'],
            ]),
            VenueOverview::make([
                'venue' => auth()->user()->venue,
                'columnSpan' => 'full',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            VenueRecentBookings::make([
                'venue' => auth()->user()->venue,
                'columnSpan' => 'full',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
        ];
    }

    #[On('dateRangeUpdated')]
    public function updateDateRange(string $startDate, string $endDate): void
    {
        $this->filters['startDate'] = $startDate;
        $this->filters['endDate'] = $endDate;
    }
}

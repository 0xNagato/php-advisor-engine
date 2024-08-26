<?php

namespace App\Filament\Pages\Venue;

use App\Livewire\Venue\VenueRecentBookings;
use App\Livewire\VenueOverview;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;
use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;

class VenueDashboard extends Dashboard
{
    use Dashboard\Concerns\HasFiltersAction;

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
        $this->filters['startDate'] = $this->filters['startDate'] ?? now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] = $this->filters['endDate'] ?? now()->format('Y-m-d');
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
            Dashboard\Actions\FilterAction::make()
                ->label('Date Range')
                ->iconButton()
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->form([
                    Actions::make([
                        Actions\Action::make('last30Days')
                            ->label('Last 30 Days')
                            ->action(function (Set $set) {
                                $set('startDate', now()->subDays(30)->format('Y-m-d'));
                                $set('endDate', now()->format('Y-m-d'));
                            }),
                        Actions\Action::make('monthToDate')
                            ->label('Month to Date')
                            ->action(function (Set $set) {
                                $set('startDate', now()->startOfMonth()->format('Y-m-d'));
                                $set('endDate', now()->format('Y-m-d'));
                            }),
                    ]),
                    DatePicker::make('startDate')
                        ->native(false),
                    DatePicker::make('endDate')
                        ->native(false),
                ]),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
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
}

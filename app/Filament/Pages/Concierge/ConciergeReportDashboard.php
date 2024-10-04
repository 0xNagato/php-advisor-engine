<?php

namespace App\Filament\Pages\Concierge;

use App\Filament\DateRangeFilterAction;
use App\Livewire\Concierge\ConciergeOverallLeaderboard;
use App\Livewire\Concierge\ConciergeRecentBookings;
use App\Livewire\ConciergeOverview;
use App\Livewire\DateRangeFilterWidget;
use Carbon\Carbon;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class ConciergeReportDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static ?string $title = 'My Earnings';

    protected static string $view = 'filament.pages.concierge.concierge-dashboard';

    protected static string $routePath = 'concierge/report';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected ?string $heading = 'My Earnings';

    protected static ?int $navigationSort = -1;

    public static function canAccess(): bool
    {
        if (session()?->exists('simpleMode')) {
            return ! session('simpleMode');
        }

        return auth()->user()?->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->filters['startDate'] ??= now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] ??= now()->format('Y-m-d');
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! isset($this->filters['startDate'], $this->filters['endDate'])) {
            return null;
        }

        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);

        $formattedStartDate = $startDate->format('M j');
        $formattedEndDate = $endDate->format('M j');

        return $formattedStartDate.' - '.$formattedEndDate;
    }

    public function getHeaderWidgets(): array
    {
        return [
            DateRangeFilterWidget::make([
                'startDate' => $this->filters['startDate'],
                'endDate' => $this->filters['endDate'],
            ]),
            ConciergeOverview::make([
                'concierge' => auth()->user()->concierge,
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            ConciergeOverview::make([
                'concierge' => auth()->user()->concierge,
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
                'isVip' => true,
            ]),
            ConciergeRecentBookings::make([
                'concierge' => auth()->user()->concierge,
                'columnSpan' => '1',
            ]),
            ConciergeOverallLeaderboard::make([
                'concierge' => auth()->user()->concierge,
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
                'columnSpan' => 1,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DateRangeFilterAction::make(),
        ];
    }

    #[On('dateRangeUpdated')]
    public function updateDateRange(string $startDate, string $endDate): void
    {
        $this->filters['startDate'] = $startDate;
        $this->filters['endDate'] = $endDate;
    }
}

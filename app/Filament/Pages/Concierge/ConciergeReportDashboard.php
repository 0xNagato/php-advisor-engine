<?php

namespace App\Filament\Pages\Concierge;

use App\Livewire\Concierge\ConciergeLeaderboard;
use App\Livewire\Concierge\ConciergeRecentBookings;
use App\Livewire\Concierge\ConciergeStats;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;

class ConciergeReportDashboard extends Dashboard
{
    use Dashboard\Concerns\HasFiltersAction;

    protected static ?string $title = 'My Earnings';

    protected static string $routePath = 'concierge/report';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected ?string $heading = 'My Earnings';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            ConciergeStats::make([
                'concierge' => auth()->user()->concierge,
                'columnSpan' => 'full',
            ]),
            ConciergeRecentBookings::make([
                'concierge' => auth()->user()->concierge,
                'columnSpan' => '1',
            ]),
            ConciergeLeaderboard::make([
                'concierge' => auth()->user()->concierge,
                'showFilters' => true,
                'columnSpan' => 1,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Dashboard\Actions\FilterAction::make()
                ->label('Date Range')
                ->icon('heroicon-o-calendar')
                ->iconButton()
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                ]),
        ];
    }
}

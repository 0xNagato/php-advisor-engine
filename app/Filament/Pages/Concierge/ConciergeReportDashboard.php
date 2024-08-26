<?php

namespace App\Filament\Pages\Concierge;

use App\Livewire\Concierge\ConciergeLeaderboard;
use App\Livewire\Concierge\ConciergeRecentBookings;
use App\Livewire\ConciergeOverview;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Contracts\Support\Htmlable;

class ConciergeReportDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static ?string $title = 'My Earnings';

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
        $this->filters['startDate'] = $this->filters['startDate'] ?? now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] = $this->filters['endDate'] ?? now()->format('Y-m-d');
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
            ConciergeOverview::make([
                'concierge' => auth()->user()->concierge,
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
            FilterAction::make()
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
}

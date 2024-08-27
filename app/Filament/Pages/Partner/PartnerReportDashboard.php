<?php

namespace App\Filament\Pages\Partner;

use App\Livewire\Partner\PartnerOverallLeaderboard;
use App\Livewire\Partner\PartnerRecentBookings;
use App\Livewire\PartnerOverview;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Contracts\Support\Htmlable;

class PartnerReportDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static ?string $title = 'My Earnings';

    protected static string $routePath = 'partner/report';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected ?string $heading = 'My Earnings';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('partner');
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
            PartnerOverview::make([
                'partner' => auth()->user()->partner,
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            PartnerRecentBookings::make([
                'partner' => auth()->user()->partner,
                'columnSpan' => '1',
            ]),
            PartnerOverallLeaderboard::make([
                'partner' => auth()->user()->partner,
                'columnSpan' => '1',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
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
                        Action::make('last30Days')
                            ->label('Last 30 Days')
                            ->action(function (Set $set) {
                                $set('startDate', now()->subDays(30)->format('Y-m-d'));
                                $set('endDate', now()->format('Y-m-d'));
                            }),
                        Action::make('monthToDate')
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

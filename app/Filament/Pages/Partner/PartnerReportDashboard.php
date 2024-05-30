<?php

namespace App\Filament\Pages\Partner;

use App\Livewire\Partner\PartnerLeaderboard;
use App\Livewire\Partner\PartnerRecentBookings;
use App\Livewire\Partner\PartnerStats;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

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
        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    public function getHeaderWidgets(): array
    {

        return [
            PartnerStats::make([
                'partner' => auth()->user()->partner,
                'columnSpan' => 'full',
            ]),
            PartnerRecentBookings::make([
                'partner' => auth()->user()->partner,
                'columnSpan' => '1',
            ]),
            PartnerLeaderboard::make([
                'partner' => auth()->user()->partner,
                'columnSpan' => '1',
            ]),
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
                ]),
        ];
    }
}

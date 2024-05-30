<?php

namespace App\Filament\Pages;

use App\Livewire\Admin\AdminRecentBookings;
use App\Livewire\Admin\AdminStats;
use App\Livewire\Concierge\ConciergeLeaderboard;
use App\Livewire\Partner\PartnerLeaderboard;
use App\Livewire\Restaurant\RestaurantLeaderboard;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

class AdminDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static ?string $title = 'Dashboard';

    protected static string $routePath = 'admin';

    protected static string $view = 'filament.pages.admin-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin');
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
            AdminStats::make(),
            AdminRecentBookings::make([
                'columnSpan' => '1',
            ]),
            RestaurantLeaderboard::make([
                'columnSpan' => '1',
            ]),
            ConciergeLeaderboard::make([
                'columnSpan' => '1',
            ]),
            PartnerLeaderboard::make([
                'columnSpan' => '1',
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
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                ]),
        ];
    }
}

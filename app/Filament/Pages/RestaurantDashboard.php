<?php

namespace App\Filament\Pages;

use App\Livewire\Restaurant\RestaurantRecentBookings;
use App\Livewire\Restaurant\RestaurantStats;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;

class RestaurantDashboard extends Dashboard
{
    use Dashboard\Concerns\HasFiltersAction;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $title = 'My Earnings';

    protected static string $routePath = 'restaurant';

    protected ?string $heading = 'My Earnings';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('restaurant');
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
            Dashboard\Actions\FilterAction::make()
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
            RestaurantStats::make(['restaurant' => auth()->user()->restaurant, 'columnSpan' => 'full']),
            RestaurantRecentBookings::make(['restaurant' => auth()->user()->restaurant, 'columnSpan' => '1']),
            // RestaurantLeaderboard::make(['restaurant' => auth()->user()->restaurant, 'columnSpan' => '1']),
        ];
    }
}

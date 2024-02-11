<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConciergeStatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->hasRole('concierge');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Earned This Month', '$1,320.00')
                ->description('$340.00 increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('Bookings', '88')
                ->description('25% increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('Cancellations', '10')
                ->description('25% decrease')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('danger'),
        ];
    }
}

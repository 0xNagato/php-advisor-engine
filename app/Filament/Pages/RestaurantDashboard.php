<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class RestaurantDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Restaurant Dashboard';

    protected static string $routePath = 'restaurant';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('restaurant');
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                ]),
        ];
    }
}

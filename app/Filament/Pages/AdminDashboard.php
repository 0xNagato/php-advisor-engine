<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;

class AdminDashboard extends Dashboard
{
    use Dashboard\Concerns\HasFiltersForm;

    protected static ?string $title = 'Super Admin Dashboard';

    protected static string $routePath = 'super-admin';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    protected function getHeaderActions(): array
    {
        return [
            Dashboard\Actions\FilterAction::make()
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                ]),
        ];
    }
}

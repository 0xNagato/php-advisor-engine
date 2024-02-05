<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;

class AdminDashboard extends Dashboard
{
    use Dashboard\Concerns\HasFiltersForm;

    protected static ?string $title = 'Admin Dashboard';

    protected static string $routePath = 'admin';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::Make('addConcierge')
                ->label('Concierge')
                ->color('success')
                ->link()
                ->icon('heroicon-m-plus-circle')
                ->url(fn (): string => route('filament.admin.resources.concierge-profiles.create')),
            Action::Make('addRestaurant')
                ->label('Restaurant')
                ->color('success')
                ->link()
                ->icon('heroicon-m-plus-circle')
                ->url(fn (): string => route('filament.admin.resources.restaurant-profiles.create')),
            Dashboard\Actions\FilterAction::make()
                ->iconButton()
                ->icon('heroicon-m-funnel')
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                ]),
        ];
    }
}

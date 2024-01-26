<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

class ConciergeDashboard extends Dashboard
{
    protected static ?string $title = 'Concierge Dashboard';

    protected static string $routePath = 'concierge';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('concierge');
    }
}

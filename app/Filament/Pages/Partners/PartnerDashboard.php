<?php

namespace App\Filament\Pages\Partners;

use App\Livewire\Partner\PartnerLeaderboard;
use App\Livewire\Partner\PartnerRecentBookings;
use App\Livewire\Partner\PartnerStats;
use Filament\Pages\Dashboard;

class PartnerDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $title = 'My Earnings';
    protected static string $view = 'filament.pages.partners.partner-dashboard';
    protected ?string $heading = 'My Earnings';

    public static function canAccess(): bool
    {
        ds(auth()->user()->partner);
        return auth()->user()?->hasRole('partner');
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

}

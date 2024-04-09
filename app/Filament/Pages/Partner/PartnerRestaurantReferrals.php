<?php

namespace App\Filament\Pages\Partner;

use Filament\Pages\Page;

class PartnerRestaurantReferrals extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.partner.partner-restaurant-referrals';

    protected static ?string $title = 'My Restaurants';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('partner');
    }
}

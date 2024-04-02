<?php

namespace App\Filament\Pages\Restaurant;

use Filament\Pages\Page;

class RestaurantAvailability extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.restaurant-availability';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('restaurant');
    }
}

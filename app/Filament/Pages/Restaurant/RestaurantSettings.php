<?php

namespace App\Filament\Pages\Restaurant;

use App\Models\Restaurant;
use Filament\Pages\Page;

class RestaurantSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.restaurant.restaurant-settings';

    public Restaurant $restaurant;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    public function mount(): void
    {
        $this->restaurant = auth()->user()->restaurant;
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MyRestaurant extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.my-restaurant';

    protected static ?int $navigationSort = 100;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    public function getHeaderWidgets(): array
    {
        return [

        ];
    }
}

<?php

namespace App\Filament\Pages\Partner;

use App\Filament\Resources\RestaurantResource\Pages\CreateRestaurant;
use App\Livewire\Partner\RestaurantReferralsTable;
use Filament\Actions\Action;
use Filament\Pages\Page;

class PartnerRestaurants extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.partner.partner-restaurant-referrals';

    protected static ?string $title = 'My Restaurants';

    protected static ?string $slug = 'partner/restaurants';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('partner');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RestaurantReferralsTable::make()
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Create Restaurant')
                ->label('Create Restaurant')
                ->icon('heroicon-o-plus-circle')
                ->url(CreateRestaurant::getUrl())
        ];
    }
}

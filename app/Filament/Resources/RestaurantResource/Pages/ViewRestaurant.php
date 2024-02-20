<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Filament\Resources\RestaurantResource;
use App\Filament\Widgets\RecentBookings;
use App\Filament\Widgets\RestaurantStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ViewRestaurant extends ViewRecord
{
    protected static string $resource = RestaurantResource::class;
    protected static string $view = 'filament.resources.restaurants.pages.view-restaurant';

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->restaurant_name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->getRecord()->user->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()->record($this->getRecord()->user),
            Actions\EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RestaurantStatsOverview::make([
                'restaurant' => $this->getRecord(),
            ]),
            RecentBookings::make([
                'type' => 'restaurant',
                'id' => $this->getRecord()->id,
            ]),
        ];
    }
}

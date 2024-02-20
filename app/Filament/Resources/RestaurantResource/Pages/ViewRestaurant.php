<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Filament\Resources\RestaurantResource;
use App\Filament\Widgets\RestaurantStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ViewRestaurant extends ViewRecord
{
    protected static string $resource = RestaurantResource::class;
    protected static string $view = 'filament.resources.restaurants.pages.view-restaurant';

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
        ];
    }
}

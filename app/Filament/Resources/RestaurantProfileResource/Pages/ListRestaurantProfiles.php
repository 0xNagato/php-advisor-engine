<?php

namespace App\Filament\Resources\RestaurantProfileResource\Pages;

use App\Filament\Resources\RestaurantProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantProfiles extends ListRecords
{
    protected static string $resource = RestaurantProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

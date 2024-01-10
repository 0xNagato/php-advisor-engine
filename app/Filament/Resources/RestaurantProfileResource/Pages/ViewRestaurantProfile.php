<?php

namespace App\Filament\Resources\RestaurantProfileResource\Pages;

use App\Filament\Resources\RestaurantProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRestaurantProfile extends ViewRecord
{
    protected static string $resource = RestaurantProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

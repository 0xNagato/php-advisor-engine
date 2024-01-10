<?php

namespace App\Filament\Resources\RestaurantProfileResource\Pages;

use App\Filament\Resources\RestaurantProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantProfile extends EditRecord
{
    protected static string $resource = RestaurantProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

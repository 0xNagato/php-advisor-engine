<?php

namespace App\Filament\Resources\RestaurantProfileResource\Pages;

use App\Filament\Resources\RestaurantProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantProfile extends CreateRecord
{
    protected static string $resource = RestaurantProfileResource::class;
}

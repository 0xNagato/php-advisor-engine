<?php

namespace App\Filament\Resources\VenueOnboardingResource\Pages;

use App\Filament\Resources\VenueOnboardingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVenueOnboardings extends ListRecords
{
    protected static string $resource = VenueOnboardingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

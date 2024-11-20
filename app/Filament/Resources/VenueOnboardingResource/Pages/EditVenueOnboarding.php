<?php

namespace App\Filament\Resources\VenueOnboardingResource\Pages;

use App\Filament\Resources\VenueOnboardingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVenueOnboarding extends EditRecord
{
    protected static string $resource = VenueOnboardingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

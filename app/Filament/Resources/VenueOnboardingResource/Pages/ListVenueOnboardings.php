<?php

namespace App\Filament\Resources\VenueOnboardingResource\Pages;

use App\Filament\Resources\VenueOnboardingResource;
use Filament\Resources\Pages\ListRecords;

class ListVenueOnboardings extends ListRecords
{
    protected static string $resource = VenueOnboardingResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->hasActiveRole('super_admin');
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}

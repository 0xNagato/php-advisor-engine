<?php

namespace App\Filament\Resources\ConciergeProfileResource\Pages;

use App\Filament\Resources\ConciergeProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConciergeProfiles extends ListRecords
{
    protected static string $resource = ConciergeProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

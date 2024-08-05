<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Filament\Resources\VenueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVenues extends ListRecords
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->iconButton()->icon('heroicon-s-plus-circle'),
        ];
    }
}

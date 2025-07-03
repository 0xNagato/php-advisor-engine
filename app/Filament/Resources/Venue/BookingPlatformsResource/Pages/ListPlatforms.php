<?php

namespace App\Filament\Resources\Venue\BookingPlatformsResource\Pages;

use App\Filament\Resources\Venue\BookingPlatformsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatforms extends ListRecords
{
    protected static string $resource = BookingPlatformsResource::class;

    public function getTitle(): string
    {
        return 'Venue Platform Connections';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Connect Platform'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Venue\CoverManagerResource\Pages;

use App\Filament\Resources\Venue\CoverManagerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCoverManagers extends ManageRecords
{
    protected static string $resource = CoverManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

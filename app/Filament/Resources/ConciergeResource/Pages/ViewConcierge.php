<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Filament\Resources\ConciergeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewConcierge extends ViewRecord
{
    protected static string $resource = ConciergeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

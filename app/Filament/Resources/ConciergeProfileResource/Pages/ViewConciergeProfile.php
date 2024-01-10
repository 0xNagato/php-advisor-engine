<?php

namespace App\Filament\Resources\ConciergeProfileResource\Pages;

use App\Filament\Resources\ConciergeProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewConciergeProfile extends ViewRecord
{
    protected static string $resource = ConciergeProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

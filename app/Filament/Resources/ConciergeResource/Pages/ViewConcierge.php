<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Filament\Resources\ConciergeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ViewConcierge extends ViewRecord
{
    protected static string $resource = ConciergeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()->record($this->getRecord()->user),
            EditAction::make(),
        ];
    }
}

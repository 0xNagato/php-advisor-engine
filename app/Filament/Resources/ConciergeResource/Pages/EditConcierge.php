<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Filament\Resources\ConciergeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConcierge extends EditRecord
{
    protected static string $resource = ConciergeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

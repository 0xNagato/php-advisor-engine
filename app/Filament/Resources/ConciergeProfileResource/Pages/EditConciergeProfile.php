<?php

namespace App\Filament\Resources\ConciergeProfileResource\Pages;

use App\Filament\Resources\ConciergeProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConciergeProfile extends EditRecord
{
    protected static string $resource = ConciergeProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\CustomerProfileResource\Pages;

use App\Filament\Resources\CustomerProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerProfile extends EditRecord
{
    protected static string $resource = CustomerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

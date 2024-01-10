<?php

namespace App\Filament\Resources\CustomerProfileResource\Pages;

use App\Filament\Resources\CustomerProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerProfile extends ViewRecord
{
    protected static string $resource = CustomerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

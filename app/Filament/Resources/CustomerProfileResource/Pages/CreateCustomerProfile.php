<?php

namespace App\Filament\Resources\CustomerProfileResource\Pages;

use App\Filament\Resources\CustomerProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerProfile extends CreateRecord
{
    protected static string $resource = CustomerProfileResource::class;
}

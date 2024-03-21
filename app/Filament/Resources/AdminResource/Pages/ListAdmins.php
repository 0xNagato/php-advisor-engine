<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdmins extends ListRecords
{
    protected static string $resource = AdminResource::class;

    protected static ?string $title = 'Admins';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Create Admin'),
        ];
    }
}

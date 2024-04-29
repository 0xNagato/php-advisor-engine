<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sender_id'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }
}

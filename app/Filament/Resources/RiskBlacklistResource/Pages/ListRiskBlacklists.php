<?php

namespace App\Filament\Resources\RiskBlacklistResource\Pages;

use App\Filament\Resources\RiskBlacklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRiskBlacklists extends ListRecords
{
    protected static string $resource = RiskBlacklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

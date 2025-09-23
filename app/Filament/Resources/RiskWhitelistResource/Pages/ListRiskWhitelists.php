<?php

namespace App\Filament\Resources\RiskWhitelistResource\Pages;

use App\Filament\Resources\RiskWhitelistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRiskWhitelists extends ListRecords
{
    protected static string $resource = RiskWhitelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use App\Livewire\Partner\PartnerLeaderboard;
use App\Livewire\Partner\PartnerRecentBookings;
use App\Livewire\Partner\PartnerStats;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ViewPartner extends ViewRecord
{
    protected static string $resource = PartnerResource::class;

    protected static string $view = 'filament.resources.partners.pages.view-partner';

    public function getHeading(): string|Htmlable
    {
        return $this->record->user->name;
    }

    public function getHeaderWidgets(): array
    {
        return [
            PartnerStats::make([
                'partner' => $this->record,
                'columnSpan' => 'full',
            ]),
            PartnerRecentBookings::make([
                'partner' => $this->record,
                'columnSpan' => '1',
            ]),
            PartnerLeaderboard::make([
                'partner' => $this->record,
                'columnSpan' => '1',
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()
                ->iconButton()
                ->record($this->getRecord()->user),
            Actions\EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->iconButton(),
        ];
    }
}

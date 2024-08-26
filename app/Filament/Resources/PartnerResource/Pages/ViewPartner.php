<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use App\Livewire\Partner\PartnerLeaderboard;
use App\Livewire\Partner\PartnerRecentBookings;
use App\Livewire\PartnerOverview;
use App\Models\Partner;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

/**
 * @method Partner getRecord()
 *
 * @property Partner $record
 */
class ViewPartner extends ViewRecord
{
    protected static string $resource = PartnerResource::class;

    public function getHeading(): string|Htmlable
    {
        return $this->record->user->name;
    }

    public function getHeaderWidgets(): array
    {
        return [
            PartnerOverview::make([
                'partner' => $this->record,
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
                ->redirectTo(config('app.platform_url'))
                ->record($this->getRecord()->user),
            EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->iconButton(),
        ];
    }
}

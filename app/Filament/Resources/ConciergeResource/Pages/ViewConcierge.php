<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Filament\Resources\ConciergeResource;
use App\Livewire\Concierge\ConciergeLeaderboard;
use App\Livewire\Concierge\ConciergeRecentBookings;
use App\Livewire\Concierge\ConciergeStats;
use App\Models\Concierge;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

/**
 * @method Concierge getRecord()
 */
class ViewConcierge extends ViewRecord
{
    protected static string $resource = ConciergeResource::class;

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->user->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()
                ->iconButton()
                ->record($this->getRecord()->user),
            EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->iconButton(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConciergeStats::make([
                'concierge' => $this->getRecord(),
                'columnSpan' => 'full',
            ]),
            ConciergeRecentBookings::make([
                'concierge' => $this->getRecord(),
                'hideConcierge' => true,
                'columnSpan' => '1',
            ]),
            ConciergeLeaderboard::make([
                'concierge' => $this->getRecord(),
                'showFilters' => false,
                'columnSpan' => '1',
            ]),
        ];
    }
}

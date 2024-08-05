<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Filament\Resources\VenueResource;
use App\Livewire\Venue\VenueLeaderboard;
use App\Livewire\Venue\VenueRecentBookings;
use App\Livewire\Venue\VenueStats;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ViewVenue extends ViewRecord
{
    protected static string $resource = VenueResource::class;

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    // public function getSubheading(): string|Htmlable|null
    // {
    //     return $this->getRecord()->user->name;
    // }

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
            VenueStats::make(['venue' => $this->getRecord(), 'columnSpan' => 'full']),
            VenueRecentBookings::make(['venue' => $this->getRecord(), 'columnSpan' => '1']),
            VenueLeaderboard::make(['venue' => $this->getRecord(), 'columnSpan' => '1']),
        ];
    }
}

<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Filament\Resources\ConciergeResource;
use App\Filament\Widgets\ConciergeStatsOverview;
use App\Filament\Widgets\RecentBookings;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ViewConcierge extends ViewRecord
{
    protected static string $resource = ConciergeResource::class;

    protected static string $view = 'filament.resources.concierges.pages.view-concierge';

    public function getHeading(): string
    {
        return $this->getRecord()->user->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->getRecord()->hotel_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()->record($this->getRecord()->user),
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConciergeStatsOverview::make([
                'concierge' => $this->getRecord(),
            ]),
            RecentBookings::make([
                'type' => 'concierge',
                'id' => $this->getRecord()->id,
            ]),
        ];
    }
}

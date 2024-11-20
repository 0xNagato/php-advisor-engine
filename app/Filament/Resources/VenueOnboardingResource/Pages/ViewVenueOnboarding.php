<?php

namespace App\Filament\Resources\VenueOnboardingResource\Pages;

use App\Filament\Resources\VenueOnboardingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVenueOnboarding extends ViewRecord
{
    protected static string $resource = VenueOnboardingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('process')
                ->action(fn () => $this->record->markAsProcessed(auth()->user()))
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'submitted')
                ->color('success')
                ->icon('heroicon-o-check'),
        ];
    }
}

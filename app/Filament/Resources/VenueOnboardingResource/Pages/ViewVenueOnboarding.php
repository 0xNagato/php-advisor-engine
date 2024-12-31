<?php

namespace App\Filament\Resources\VenueOnboardingResource\Pages;

use App\Actions\GenerateVenueAgreement;
use App\Filament\Resources\VenueOnboardingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVenueOnboarding extends ViewRecord
{
    protected static string $resource = VenueOnboardingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_agreement')
                ->label('Download Agreement')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $pdfContent = GenerateVenueAgreement::run($this->record);

                    return response()->streamDownload(
                        fn () => print ($pdfContent),
                        'prima-venue-agreement.pdf',
                        ['Content-Type' => 'application/pdf']
                    );
                })
                ->color('gray'),

            Action::make('process')
                ->action(fn () => $this->record->markAsProcessed(auth()->user()))
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'submitted')
                ->color('success')
                ->icon('heroicon-o-check'),
        ];
    }
}

<?php

namespace App\Filament\Resources\VenueOnboardingResource\Pages;

use App\Actions\GenerateVenueAgreement;
use App\Filament\Resources\VenueOnboardingResource;
use App\Notifications\VenueAgreementCopy;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Notification as NotificationFacade;

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

            Action::make('resend_agreement')
                ->label('Resend Agreement')
                ->icon('heroicon-o-envelope')
                ->action(function () {
                    NotificationFacade::route('mail', $this->record->email)
                        ->notify(new VenueAgreementCopy($this->record));

                    Notification::make()
                        ->title('Agreement sent successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Resend Agreement')
                ->modalDescription('Are you sure you want to resend the agreement to '.$this->record->email.'?')
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

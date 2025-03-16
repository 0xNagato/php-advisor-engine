<?php

namespace App\Filament\Resources\VenueOnboardingResource\Pages;

use App\Actions\GenerateVenueAgreement;
use App\Actions\MergeVenueOnboardingWithGroup;
use App\Actions\ProcessVenueOnboarding;
use App\Filament\Resources\VenueOnboardingResource;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use App\Notifications\VenueAgreementCopy;
use App\Notifications\WelcomeVenueManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Validation\ValidationException;

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

            Action::make('mergeWithVenueGroup')
                ->label('Merge with Venue Group')
                ->icon('heroicon-o-building-office-2')
                ->form([
                    Select::make('venue_group_id')
                        ->label('Venue Group')
                        ->options(VenueGroup::query()->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    TextInput::make('booking_fee')
                        ->label('Booking Fee')
                        ->prefix('$')
                        ->default(Venue::DEFAULT_BOOKING_FEE)
                        ->numeric()
                        ->required(),
                    TextInput::make('payout_venue')
                        ->label('Venue Payout')
                        ->default(Venue::DEFAULT_PAYOUT_VENUE)
                        ->numeric()
                        ->suffix('%')
                        ->required(),
                    Textarea::make('notes')
                        ->label('Processing Notes')
                        ->rows(5)
                        ->placeholder('Enter any notes about merging this onboarding with the venue group')
                        ->required(),
                ])
                ->action(function (array $data) {
                    /** @var User $user */
                    $user = auth()->user();
                    $venueGroup = VenueGroup::findOrFail($data['venue_group_id']);

                    try {
                        app(MergeVenueOnboardingWithGroup::class)->execute(
                            onboarding: $this->record,
                            venueGroup: $venueGroup,
                            processedBy: $user,
                            notes: $data['notes'],
                            venueDefaults: [
                                'payout_venue' => $data['payout_venue'],
                                'booking_fee' => $data['booking_fee'],
                            ]
                        );

                        Notification::make()
                            ->title('Onboarding merged with venue group successfully')
                            ->success()
                            ->send();
                    } catch (ValidationException $e) {
                        foreach ($e->errors() as $error) {
                            Notification::make()
                                ->title($error[0])
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error merging with venue group: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Merge with Venue Group')
                ->modalDescription('This will create venues from this onboarding and add them to the selected venue group.')
                ->visible(fn (): bool => $this->record->status === 'submitted')
                ->color('success')
                ->icon('heroicon-o-building-office-2'),

            Action::make('process')
                ->action(function (array $data) {
                    /** @var User $user */
                    $user = auth()->user();

                    try {
                        app(ProcessVenueOnboarding::class)->execute(
                            onboarding: $this->record,
                            processedBy: $user,
                            notes: $data['notes'],
                            venueDefaults: [
                                'payout_venue' => $data['payout_venue'],
                                'booking_fee' => $data['booking_fee'],
                            ]
                        );

                        Notification::make()
                            ->title('Venue onboarding processed successfully')
                            ->success()
                            ->send();
                    } catch (ValidationException $e) {
                        foreach ($e->errors() as $error) {
                            Notification::make()
                                ->title($error[0])
                                ->danger()
                                ->send();
                        }
                    }
                })
                ->form([
                    TextInput::make('booking_fee')
                        ->label('Booking Fee')
                        ->prefix('$')
                        ->default(Venue::DEFAULT_BOOKING_FEE)
                        ->numeric()
                        ->required(),
                    TextInput::make('payout_venue')
                        ->label('Venue Payout')
                        ->default(Venue::DEFAULT_PAYOUT_VENUE)
                        ->numeric()
                        ->suffix('%')
                        ->required(),
                    Textarea::make('notes')
                        ->label('Processing Notes')
                        ->rows(5)
                        ->placeholder('Enter any notes about processing this onboarding request')
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Process Venue Onboarding')
                ->modalDescription('This will create the venue manager and all associated venues.')
                ->visible(fn (): bool => $this->record->status === 'submitted')
                ->color('success')
                ->icon('heroicon-o-check'),

            Action::make('resend_welcome')
                ->label('Resend Welcome Email')
                ->icon('heroicon-o-envelope')
                ->action(function () {
                    // Find the venue manager user associated with this onboarding
                    /** @var User|null $managerUser */
                    $managerUser = User::query()
                        ->where('email', $this->record->email)
                        ->first();

                    if (! $managerUser) {
                        Notification::make()
                            ->title('Venue manager not found')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Get the venue data for the notification
                    $venueData = $this->record->locations
                        ->map(fn ($location) => [
                            'name' => $location->name,
                        ])
                        ->toArray();

                    // Send the welcome notification
                    $managerUser->notify(new WelcomeVenueManager($managerUser, $venueData));

                    Notification::make()
                        ->title('Welcome email resent successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Resend Welcome Email')
                ->modalDescription('Are you sure you want to resend the welcome email to '.$this->record->email.'?')
                ->visible(fn (): bool => $this->record->status === 'completed')
                ->color('gray'),
        ];
    }
}

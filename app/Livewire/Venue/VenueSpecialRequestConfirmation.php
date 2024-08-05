<?php

namespace App\Livewire\Venue;

use App\Data\SpecialRequest\SpecialRequestConversionData;
use App\Enums\SpecialRequestStatus;
use App\Models\SpecialRequest;
use App\Notifications\Concierge\VenueSpecialRequestAccepted;
use App\Notifications\Concierge\VenueSpecialRequestChangeRequest;
use App\Notifications\Concierge\VenueSpecialRequestRejected;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Livewire\Attributes\Computed;

/**
 * @property Form $approvalForm
 * @property Form $requestChangesForm
 */
class VenueSpecialRequestConfirmation extends Page
{
    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.venue-special-request-confirmation';

    public SpecialRequest $specialRequest;

    public ?array $approvalFormData = [];

    public ?array $requestChangesFormData = [];

    public $showRequestChangesForm = false;

    public function mount(string $token): void
    {
        abort_unless(boolean: request()?->hasValidSignature(), code: 401);

        $this->specialRequest = SpecialRequest::query()
            ->where('uuid', $token)
            ->firstOrFail();

        $this->approvalForm->fill();
        $this->requestChangesForm->fill([
            'commission_requested_percentage' => $this->specialRequest->commission_requested_percentage,
            'minimum_spend' => $this->specialRequest->minimum_spend,
        ]);
    }

    protected function getForms(): array
    {
        return [
            'approvalForm',
            'requestChangesForm',
        ];
    }

    public function approvalForm(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('venue_message')
                    ->hiddenLabel()
                    ->placeholder('Message to the Concierge (optional)'),
                Grid::make()
                    ->columns(1)
                    ->extraAttributes(['class' => 'grid-buttons'])
                    ->schema([
                        Actions::make([
                            Action::make('showRequestChangesForm')
                                ->label('Make Changes')
                                ->action(fn () => ($this->showRequestChangesForm = true))
                                ->visible(fn () => $this->showRequestChangesForm === false)
                                ->button(),
                        ])->fullWidth(),
                        Actions::make([
                            Action::make('confirmRequest')
                                ->label('Accept Request')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading('Are you sure you want to accept this request?')
                                ->modalDescription(null)
                                ->button()
                                ->action(fn () => $this->handleConfirmRequest()),
                        ])->fullWidth(),
                        Actions::make([
                            Action::make('denyRequest')
                                ->label('Deny Request')
                                ->requiresConfirmation()
                                ->modalHeading('Are you sure you want to deny this request?')
                                ->modalDescription(null)
                                ->color(Color::Gray)
                                ->button()
                                ->action(fn () => $this->handleDenyRequest()),
                        ])->fullWidth(),
                    ]),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->statePath('approvalFormData');
    }

    public function requestChangesForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('commission_requested_percentage')
                    ->label('Commission')
                    ->hiddenLabel()
                    ->placeholder('Commission')
                    ->numeric()
                    ->suffix('%')
                    ->default(10)
                    ->maxValue(15)
                    ->live()
                    ->required(),
                TextInput::make('minimum_spend')
                    ->label('Minimum Spend')
                    ->hiddenLabel()
                    ->placeholder('00.00')
                    ->numeric()
                    ->mask(RawJs::make('$money($input)'))
                    ->prefix($this->specialRequest->venue->inRegion->currency_symbol)
                    ->stripCharacters(',')
                    ->live()
                    ->required(),
                Textarea::make('message')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->placeholder('Message to the Concierge (optional)'),
                Actions::make([
                    Action::make('requestChanges')
                        ->label('Submit Changes')
                        ->requiresConfirmation()
                        ->modalHeading('Are you sure you want to submit these changes?')
                        ->modalDescription(null)
                        ->action(fn () => $this->handleRequestChange())
                        ->button(),
                    Action::make('cancel')
                        ->label('Cancel')
                        ->color(Color::Gray)
                        ->action(fn () => ($this->showRequestChangesForm = false))
                        ->visible(fn () => $this->showRequestChangesForm === true)
                        ->button(),
                ])
                    ->columnSpanFull()
                    ->fullWidth(),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('requestChangesFormData');
    }

    public function handleConfirmRequest(): void
    {
        $data = $this->approvalForm->getState();

        $this->specialRequest->update([
            'status' => SpecialRequestStatus::ACCEPTED,
            'venue_message' => $data['venue_message'],
        ]);

        $this->specialRequest->concierge->user->notify(new VenueSpecialRequestAccepted($this->specialRequest));

        Notification::make()
            ->title('Special Request Accepted')
            ->success()
            ->send();
    }

    public function handleDenyRequest(): void
    {
        $data = $this->approvalForm->getState();

        $this->specialRequest->update([
            'status' => SpecialRequestStatus::REJECTED,
            'venue_message' => $data['venue_message'],
        ]);

        $this->specialRequest->concierge->user->notify(new VenueSpecialRequestRejected($this->specialRequest));

        Notification::make()
            ->title('Special Request Rejected')
            ->success()
            ->send();
    }

    public function handleRequestChange(): void
    {
        $data = $this->requestChangesForm->getState();

        $conversions = $this->specialRequest->conversations;
        $conversions[] = new SpecialRequestConversionData(
            name: $this->specialRequest->venue->name,
            minimum_spend: $data['minimum_spend'],
            commission_requested_percentage: $data['commission_requested_percentage'],
            message: $data['message'],
            created_at: now(),
        );

        $this->specialRequest->update([
            'status' => SpecialRequestStatus::AWAITING_REPLY,
            'conversations' => $conversions,
        ]);

        $this->specialRequest->concierge->user->notify(new VenueSpecialRequestChangeRequest($this->specialRequest));

        Notification::make()
            ->title('Special Request Changes Submitted')
            ->success()
            ->send();
    }

    #[Computed]
    public function venueTotalFee(): float
    {
        $commissionValue = ($this->commissionRequestedPercentage() / 100) * $this->minimumSpend();
        $platformFee = (SpecialRequest::PLATFORM_PERCENTAGE / 100) * $commissionValue;

        return $commissionValue + $platformFee;
    }

    #[Computed]
    public function minimumSpend(): int
    {
        return (int) str_replace(',', '', $this->requestChangesFormData['minimum_spend']);
    }

    #[Computed]
    public function commissionRequestedPercentage(): float
    {
        return $this->requestChangesFormData['commission_requested_percentage'];
    }

    #[Computed]
    public function confirmationMessage(): string
    {
        return match ($this->specialRequest->status) {
            SpecialRequestStatus::AWAITING_REPLY => 'Your requested changes are being sent to the concierge now. Once the concierge responds, we will notify you!',
            SpecialRequestStatus::ACCEPTED => 'Thank you for accepting the special request! The client and concierge are being notified now.',
            SpecialRequestStatus::REJECTED => 'The special request has been denied. We will notify the client and concierge and they may resubmit another offer. Thank you!',
        };
    }
}

<?php

namespace App\Livewire\Restaurant;

use App\Enums\SpecialRequestStatus;
use App\Events\SpecialRequestAccepted;
use App\Events\SpecialRequestRejected;
use App\Models\SpecialRequest;
use Filament\Forms\Components\Actions;
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
class RestaurantSpecialRequestConfirmation extends Page
{
    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.restaurant-special-request-confirmation';

    public SpecialRequest $specialRequest;

    public ?array $approvalFormData = [];

    public ?array $requestChangesFormData = [];

    public $showRequestChangesForm = false;

    public $formSubmitted = false;

    public function mount(string $token): void
    {
        if (! request()?->hasValidSignature()) {
            abort(401);
        }

        $this->specialRequest = SpecialRequest::where('uuid', $token)->firstOrFail();
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
        return $form->schema([
            Textarea::make('restaurant_message')
                ->hiddenLabel()
                ->placeholder('Message to the Concierge (optional)'),

            Grid::make()
                ->columns(1)
                ->extraAttributes(['class' => 'grid-buttons'])
                ->schema([
                    Actions::make([
                        Actions\Action::make('showRequestChangesForm')
                            ->label('Make Changes')
                            ->action(fn () => $this->showRequestChangesForm = true)
                            ->visible(fn () => $this->showRequestChangesForm === false)
                            ->size('xs')
                            ->button(),
                    ])
                        ->fullWidth(),
                    Actions::make([
                        Actions\Action::make('confirmRequest')
                            ->label('Accept Request')
                            ->color('success')
                            ->requiresConfirmation('Are you sure you want to accept this request?')
                            ->size('xs')
                            ->button(),
                    ])
                        ->fullWidth(),
                    Actions::make([
                        Actions\Action::make('denyRequest')
                            ->label('Deny Request')
                            ->requiresConfirmation('Are you sure you want to deny this request?')
                            ->color(Color::Gray)
                            ->size('xs')
                            ->button(),
                    ])
                        ->fullWidth(),
                ]),
        ])
            ->extraAttributes(['class' => 'inline-form'])
            ->statePath('approvalFormData');
    }

    public function requestChangesForm(Form $form): Form
    {
        return $form->schema([
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
                ->prefix($this->specialRequest->restaurant->inRegion->currency_symbol)
                ->stripCharacters(',')
                ->live()
                ->required(),
            Textarea::make('restaurant_message')
                ->hiddenLabel()
                ->columnSpanFull()
                ->placeholder('Message to the Concierge (optional)'),
            Actions::make([
                Actions\Action::make('requestChanges')
                    ->label('Submit Changes')
                    ->requiresConfirmation('Are you sure you want to request changes?')
                    ->button(),
                Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->color(Color::Gray)
                    ->action(fn () => $this->showRequestChangesForm = false)
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

    public function confirmRequest(): void
    {
        $this->specialRequest->update([
            'status' => SpecialRequestStatus::Accepted,
        ]);

        SpecialRequestAccepted::dispatch($this->specialRequest);

        Notification::make()
            ->title('Special Request Accepted')
            ->success()
            ->send();
    }

    public function denyRequest(): void
    {
        $this->specialRequest->update([
            'status' => SpecialRequestStatus::Rejected,
        ]);

        SpecialRequestRejected::dispatch($this->specialRequest);

        Notification::make()
            ->title('Special Request Rejected')
            ->success()
            ->send();
    }

    #[Computed]
    public function restaurantTotalFee(): float
    {
        $commissionValue = ($this->commissionRequestedPercentage() / 100) * $this->minimumSpend();
        $platformFee = 0.07 * $commissionValue;

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
}

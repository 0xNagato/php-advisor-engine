<?php

namespace App\Filament\Pages\Restaurant;

use App\Models\Region;
use App\Models\Restaurant;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\RawJs;

/**
 * @property Form $bookingFeesForm
 * @property Form $specialPricingForm
 * @property Form $nonPrimeFeesForm
 */
class PaymentStructure extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.restaurant.payment-structure';

    protected static ?int $navigationSort = 29;

    public Restaurant $restaurant;

    public Region $region;

    public ?array $bookingFeesFormData = [];

    public ?array $specialPricingFormData = [];

    public ?array $nonPrimeFeesFormData = [];

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    public function mount(): void
    {
        $this->restaurant = auth()->user()->restaurant;
        $this->region = $this->restaurant->inRegion;

        $this->bookingFeesForm->fill([
            'booking_fee' => $this->restaurant->booking_fee,
            'increment_fee' => $this->restaurant->increment_fee,
            'minimum_spend' => $this->restaurant->minimum_spend,
        ]);

        $this->nonPrimeFeesForm->fill([
            'non_prime_type' => $this->restaurant->non_prime_type,
            'non_prime_fee_per_head' => $this->restaurant->non_prime_fee_per_head,
        ]);

        $this->specialPricingForm->fill([
            'special_pricing' => $this->restaurant->specialPricing,
        ]);
    }

    protected function getForms(): array
    {
        return [
            'bookingFeesForm',
            'nonPrimeFeesForm',
            'specialPricingForm',
        ];
    }

    public function bookingFeesForm(Form $form): Form
    {
        return $form->schema([
            TextInput::make('booking_fee')
                ->label('Booking Fee')
                ->helperText('Enter the base booking fee for a standard reservation (2 people). This fee will be charged regardless of the number of guests.')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->prefix(fn () => $this->region->currency_symbol)
                ->required(),
            TextInput::make('increment_fee')
                ->label('Increment Fee (per customer)')
                ->helperText('Specify the additional fee per person exceeding the standard reservation size (2 people). This fee will be applied for each additional guest.')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->prefix(fn () => $this->region->currency_symbol),
            TextInput::make('minimum_spend')
                ->label('Minimum Spend (per customer)')
                ->helperText('Define the minimum spend per person required for a booking. The concierge will communicate this amount to the customer, and their agreement will be obtained before confirming the booking.')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->prefix(fn () => $this->region->currency_symbol),
        ])
            ->statePath('bookingFeesFormData')
            ->model($this->restaurant);
    }

    public function saveBookingFeesForm(): void
    {
        $data = $this->bookingFeesForm->getState();
        $this->restaurant->update([
            'booking_fee' => $data['booking_fee'],
            'increment_fee' => $data['increment_fee'],
            'minimum_spend' => $data['minimum_spend'],
        ]);

        Notification::make()
            ->title('Booking fee updated successfully.')
            ->success()
            ->send();
    }

    public function nonPrimeFeesForm(Form $form): Form
    {
        return $form->schema([
            Radio::make('non_prime_type')
                ->label('Non-Prime Type')
                ->options([
                    'paid' => 'Paid',
                    'free' => 'Free',
                ])
                ->inline()
                ->default('paid')
                ->live()
                ->required(),
            TextInput::make('non_prime_fee_per_head')
                ->label('Non-Prime Bounty (per customer)')
                ->helperText('Enter the additional fee per person for non-prime hours. This fee will be charged for each guest during non-prime hours.')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->minValue(5)
                ->prefix(fn () => $this->region->currency_symbol)
                ->hidden(fn (Get $get) => $get('non_prime_type') === 'free')
                ->required(),
        ])
            ->statePath('nonPrimeFeesFormData')
            ->model($this->restaurant);
    }

    public function saveNonPrimeFeesForm(): void
    {
        $data = $this->nonPrimeFeesForm->getState();
        $this->restaurant->update([
            'non_prime_type' => $data['non_prime_type'],
            'non_prime_fee_per_head' => $data['non_prime_fee_per_head'] ?? $this->restaurant->non_prime_fee_per_head,
        ]);

        Notification::make()
            ->title('Non-prime fees updated successfully.')
            ->success()
            ->send();
    }

    public function specialPricingForm(Form $form): Form
    {
        return $form->schema([
            Repeater::make('special_pricing')
                ->hiddenLabel()
                ->reorderable(false)
                ->addActionLabel('Add New Day')
                ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                ->label('Special Pricing')
                ->schema([
                    DatePicker::make('date')
                        ->label('Date')
                        ->native(false)
                        ->required(),
                    TextInput::make('fee')
                        ->label('Fee')
                        ->prefix(fn () => $this->region->currency_symbol)
                        ->numeric()
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->required(),
                ]),
        ])
            ->statePath('specialPricingFormData')
            ->model($this->restaurant);
    }

    public function saveSpecialPricingForm(): void
    {
        $data = $this->specialPricingForm->getState();

        $this->restaurant->specialPricing()->delete();
        $this->restaurant->specialPricing()->createMany($data['special_pricing']);

        Notification::make()
            ->title('Special pricing updated successfully.')
            ->success()
            ->send();
    }
}

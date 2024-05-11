<?php

namespace App\Filament\Pages\Restaurant;

use App\Models\Region;
use App\Models\Restaurant;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\RawJs;

/**
 * @property Form $bookingFeesForm
 * @property Form $specialPricingForm
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

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    public function mount(): void
    {
        $this->restaurant = auth()->user()->restaurant ?? abort(403);
        $this->region = $this->restaurant->inRegion;

        $this->bookingFeesForm->fill([
            'booking_fee' => $this->restaurant->booking_fee,
            'increment_fee' => $this->restaurant->increment_fee,
            'minimum_spend' => $this->restaurant->minimum_spend,
        ]);

        $this->specialPricingForm->fill([
            'special_pricing' => $this->restaurant->specialPricing,
        ]);
    }

    protected function getForms(): array
    {
        return [
            'bookingFeesForm',
            'specialPricingForm',
        ];
    }

    public function bookingFeesForm(Form $form): Form
    {
        return $form->schema([
            TextInput::make('booking_fee')
                ->label('Booking Fee')
                ->helperText('This is the minimum cost for a reservation for for a prime-time reservation.')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->prefix(fn () => $this->region->currency_symbol)
                ->required(),
            TextInput::make('increment_fee')
                ->label('Increment Fee (per customer)')
                ->helperText('The base reservation is for 2 people. For each additional diner, this fee will apply.')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->prefix(fn () => $this->region->currency_symbol),
            // TextInput::make('minimum_spend')
            //     ->label('Minimum Spend (per customer)')
            //     ->helperText('Define the minimum spend per person required for a booking. The concierge will communicate this amount to the customer, and their agreement will be obtained before confirming the booking.')
            //     ->mask(RawJs::make('$money($input)'))
            //     ->stripCharacters(',')
            //     ->numeric()
            //     ->prefix(fn () => $this->region->currency_symbol),
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
            // 'minimum_spend' => $data['minimum_spend'],
        ]);

        Notification::make()
            ->title('Booking fee updated successfully.')
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

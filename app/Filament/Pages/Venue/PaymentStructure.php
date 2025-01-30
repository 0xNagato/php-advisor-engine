<?php

namespace App\Filament\Pages\Venue;

use App\Models\Region;
use App\Models\User;
use App\Models\Venue;
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

    protected static string $view = 'filament.pages.venue.payment-structure';

    protected static ?string $title = 'Booking Fees';

    protected static ?int $navigationSort = 29;

    public Venue $venue;

    public Region $region;

    public ?array $bookingFeesFormData = [];

    public ?array $specialPricingFormData = [];

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasActiveRole('venue_manager')) {
            $currentVenue = auth()->user()?->currentVenueGroup()?->currentVenue(auth()->user());

            return $currentVenue?->name ?? 'Venue Management';
        }

        return null;
    }

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasActiveRole('venue')) {
            return true;
        }

        if ($user->hasActiveRole('venue_manager')) {
            $venueGroup = $user->currentVenueGroup();

            return filled($venueGroup?->getAllowedVenueIds($user));
        }

        return false;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->hasActiveRole(['venue', 'venue_manager']), 403);

        if (auth()->user()->hasActiveRole('venue')) {
            $this->venue = auth()->user()->venue;
        } elseif (auth()->user()->hasActiveRole('venue_manager')) {
            $venueGroup = auth()->user()->currentVenueGroup();
            $currentVenue = $venueGroup?->currentVenue(auth()->user());

            abort_unless((bool) $currentVenue, 404, 'No active venue selected');
            $this->venue = $currentVenue;
        } else {
            abort(403, 'You are not authorized to access this page');
        }

        $this->region = $this->venue->inRegion;

        $this->bookingFeesForm->fill([
            'booking_fee' => $this->venue->booking_fee,
            'increment_fee' => $this->venue->increment_fee,
            'minimum_spend' => $this->venue->minimum_spend,
        ]);

        $this->specialPricingForm->fill([
            'special_pricing' => $this->venue->specialPricing,
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
                ->helperText('This is the amount charged for a prime-time reservation for 2 diners.')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->prefix(fn () => $this->region->currency_symbol)
                ->required(),
            TextInput::make('increment_fee')
                ->label('Additional Fee (per customer)')
                ->helperText('The base reservation is for 2 people. For each additional diner, this fee will apply.')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->prefix(fn () => $this->region->currency_symbol),
        ])
            ->statePath('bookingFeesFormData')
            ->model($this->venue);
    }

    public function saveBookingFeesForm(): void
    {
        $data = $this->bookingFeesForm->getState();
        $this->venue->update([
            'booking_fee' => $data['booking_fee'],
            'increment_fee' => $data['increment_fee'],
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
                        ->native(true)
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
            ->model($this->venue);
    }

    public function saveSpecialPricingForm(): void
    {
        $data = $this->specialPricingForm->getState();

        $this->venue->specialPricing()->delete();
        $this->venue->specialPricing()->createMany($data['special_pricing']);

        Notification::make()
            ->title('Special pricing updated successfully.')
            ->success()
            ->send();
    }
}

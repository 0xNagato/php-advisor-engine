<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Filament\Resources\RestaurantResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Button;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->restaurant_name;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Restaurant Information')
                    ->icon('heroicon-m-building-storefront')
                    ->schema([
                        TextInput::make('restaurant_name')
                            ->label('Restaurant Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('primary_contact_name')
                            ->label('Primary Contact Name')
                            ->required(),
                        PhoneInput::make('contact_phone')
                            ->label('Primary Contact Phone')
                            ->required()
                            ->onlyCountries(['US', 'CA'])
                            ->initialCountry('US'),

                    ]),

                Repeater::make('contacts')
                    ->addActionLabel('Add Contact')
                    ->label('Contacts')
                    ->schema([
                        TextInput::make('contact_name')
                            ->label('Contact Name')
                            ->required(),
                        PhoneInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->required()
                            ->onlyCountries(['US', 'CA'])
                            ->initialCountry('US'),
                        Checkbox::make('use_for_reservations')
                            ->label('Use for Reservations')
                            ->default(true),
                    ]),

                Section::make('Payout Information')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        TextInput::make('booking_fee')
                            ->label('Booking Fee')
                            ->prefix('$')
                            ->default(200)
                            ->numeric()
                            ->required(),
                        TextInput::make('payout_restaurant')
                            ->label('Payout Restaurant')
                            ->default(60)
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                    ]),
                Section::make('Special Pricing')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        Repeater::make('special_pricing')
                            ->hiddenLabel()
                            ->relationship('specialPricing')
                            ->addActionLabel('Add Day')
                            ->label('Special Pricing')
                            ->schema([
                                TextInput::make('date')
                                    ->label('Date')
                                    ->type('date')
                                    ->required(),
                                TextInput::make('fee')
                                    ->label('Fee')
                                    ->prefix('$')
                                    ->numeric()
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public function toggleSuspend(): void
    {
        $this->getRecord()->update([
            'is_suspended' => !$this->getRecord()->is_suspended,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make($this->getRecord()->is_suspended ? 'Restore' : 'Suspend')
                ->action('toggleSuspend')
                ->requiresConfirmation()
                ->color($this->getRecord()->is_suspended ? 'success' : 'danger')
        ];
    }
}

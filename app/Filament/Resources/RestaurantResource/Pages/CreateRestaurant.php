<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Filament\Resources\RestaurantResource;
use App\Models\User;
use App\Notifications\RestaurantCreated;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Str;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CreateRestaurant extends CreateRecord
{
    protected static string $resource = RestaurantResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Registration')
                    ->icon('heroicon-m-user')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->placeholder('First Name')
                            ->autocomplete(false)
                            ->required(),
                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->placeholder('Last Name')
                            ->autocomplete(false)
                            ->required(),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->placeholder('name@domain.com')
                            ->autocomplete(false)
                            ->email()
                            ->unique(User::class, 'email')
                            ->required(),
                        PhoneInput::make('phone')
                            ->label('Phone Number')
                            ->placeholder('Phone Number')
                            ->hint('Used for SMS notifications')
                            ->onlyCountries(['US', 'CA'])
                            ->initialCountry('US')
                            ->required(),
                    ]),
                Section::make('Restaurant Information')
                    ->icon('heroicon-m-building-storefront')
                    ->schema([
                        TextInput::make('restaurant_name')
                            ->label('Restaurant Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('primary_contact_name')
                            ->label('Primary Contact Name')
                            ->required()
                            ->maxLength(255),
                        PhoneInput::make('contact_phone')
                            ->label('Primary Contact Number')
                            ->onlyCountries(['US'])
                            ->initialCountry('US')
                            ->required(),
                        TextInput::make('secondary_contact_name')
                            ->label('Secondary Contact Name')
                            ->maxLength(255),
                        PhoneInput::make('secondary_contact_phone')
                            ->label('Secondary Contact Number')
                            ->onlyCountries(['US'])
                            ->initialCountry('US'),
                        TextInput::make('booking_fee')
                            ->label('Booking Fee')
                            ->prefix('$')
                            ->default(200)
                            ->numeric()
                            ->required(),
                    ]),
                Section::make('Payout Information')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        TextInput::make('payout_restaurant')
                            ->label('Payout Restaurant')
                            ->default(60)
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        TextInput::make('payout_charity')
                            ->label('Payout Charity')
                            ->default(5)
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        TextInput::make('payout_concierge')
                            ->label('Payout Concierge')
                            ->default(15)
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        TextInput::make('payout_platform')
                            ->label('Payout Platform')
                            ->default(20)
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Str::random(8),
        ]);

        $user->assignRole('restaurant');
        $user->notify(new RestaurantCreated($user));

        return $user->restaurant()->create([
            'restaurant_name' => $data['restaurant_name'],
            'contact_phone' => $data['contact_phone'],
            'primary_contact_name' => $data['primary_contact_name'],
            'secondary_contact_name' => $data['secondary_contact_name'],
            'secondary_contact_phone' => $data['secondary_contact_phone'],
            'payout_restaurant' => $data['payout_restaurant'],
            'payout_charity' => $data['payout_charity'],
            'payout_concierge' => $data['payout_concierge'],
            'payout_platform' => $data['payout_platform'],
            'booking_fee' => $data['booking_fee'] * 100,
        ]);
    }
}

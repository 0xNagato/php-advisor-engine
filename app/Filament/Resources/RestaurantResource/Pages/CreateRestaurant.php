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
                        TextInput::make('name')
                            ->label('Full Name')
                            ->placeholder('Full Name')
                            ->autocomplete(false)
                            ->required(),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->placeholder('name@domain.com')
                            ->autocomplete(false)
                            ->email()
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
                            ->required()
                            ->maxLength(255),
                        PhoneInput::make('secondary_contact_phone')
                            ->label('Secondary Contact Number')
                            ->onlyCountries(['US'])
                            ->initialCountry('US')
                            ->required(),
                    ]),
                Section::make('Payout Information')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        TextInput::make('payout_percentage')
                            ->label('Payout Percentage')
                            ->default(60)
                            ->hint('Percentage of the total booking')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Str::random(8),
        ]);

        $user->assignRole('restaurant');
        $user->notify(new RestaurantCreated($user));

        return $user->restaurantProfile()->create([
            'restaurant_name' => $data['restaurant_name'],
            'contact_phone' => $data['contact_phone'],
            'website_url' => $data['website_url'],
            'description' => $data['description'],
            'cuisines' => $data['cuisines'],
            'price_range' => $data['price_range'],
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip' => $data['zip'],
            'payout_percentage' => $data['payout_percentage'],
        ]);
    }
}

<?php

namespace App\Filament\Resources\RestaurantProfileResource\Pages;

use App\Filament\Resources\RestaurantProfileResource;
use App\Models\User;
use App\Notifications\RestaurantCreated;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Str;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CreateRestaurantProfile extends CreateRecord
{
    protected static string $resource = RestaurantProfileResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Registration')
                    ->description('This will be the login information for the restaurant contact. The restaurant contact will receive an email and sms with a link to set their password.')
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
                    ->description('This will be the restaurant information that is displayed for each listing in the concierge view.')
                    ->icon('heroicon-m-building-storefront')
                    ->schema([
                        TextInput::make('restaurant_name')
                            ->label('Restaurant Name')
                            ->required()
                            ->maxLength(255),
                        PhoneInput::make('contact_phone')
                            ->label('Restaurant Phone Number')
                            ->onlyCountries(['US'])
                            ->initialCountry('US')
                            ->required(),
                        TagsInput::make('cuisines')
                            ->placeholder('New cuisine')
                            ->suggestions(['American', 'Italian', 'Mexican', 'Chinese', 'Japanese', 'Indian', 'Thai', 'Mediterranean', 'French', 'Greek', 'Korean', 'Vietnamese', 'Spanish', 'German', 'Brazilian', 'Caribbean', 'African', 'Middle Eastern', 'Other'])
                            ->required(),
                        Select::make('price_range')
                            ->label('Price Range')
                            ->options([
                                1 => '$',
                                2 => '$$',
                                3 => '$$$',
                                4 => '$$$$',
                                5 => '$$$$$',
                            ])
                            ->required(),
                        TextInput::make('website_url')
                            ->label('Website URL')
                            ->url()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        TextInput::make('address_line_1')
                            ->label('Address Line 1')
                            ->maxLength(255),
                        TextInput::make('address_line_2')
                            ->label('Address Line 2')
                            ->maxLength(255),
                        TextInput::make('city')
                            ->maxLength(255),
                        TextInput::make('state')
                            ->maxLength(255),
                        TextInput::make('zip')
                            ->label('Zip Code')
                            ->maxLength(255),
                    ]),
                Section::make('Payout Information')
                    ->description('This will be the payout information for the restaurant.')
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

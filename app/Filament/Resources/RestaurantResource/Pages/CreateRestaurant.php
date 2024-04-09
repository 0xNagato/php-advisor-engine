<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Events\RestaurantCreated;
use App\Filament\Resources\RestaurantResource;
use App\Models\Referral;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
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
                            ->required(),
                        PhoneInput::make('contact_phone')
                            ->label('Primary Contact Phone')
                            ->required()
                            ->onlyCountries(['US', 'CA'])
                            ->initialCountry('US'),

                    ]),

                Repeater::make('contacts')
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

        Referral::create([
            'user_id' => $user->id,
            'type' => 'restaurant',
            'referrer_type' => auth()->user()->main_role,
            'referrer_id' => auth()->id(),
            'email' => $data['email'],
            'phone' => $data['phone'],
        ]);

        $user->assignRole('restaurant');

        // We are going to notify the restaurant once the partner
        // has set up the restaurant account.
        // $user->notify(new RestaurantCreated($user));

        $restaurant = $user->restaurant()->create([
            'restaurant_name' => $data['restaurant_name'],
            'primary_contact_name' => $data['primary_contact_name'],
            'contact_phone' => $data['contact_phone'],
            'payout_restaurant' => $data['payout_restaurant'],
            'booking_fee' => $data['booking_fee'] * 100,
            'contacts' => $data['contacts'],
            'open_days' => [
                'monday' => 'open',
                'tuesday' => 'open',
                'wednesday' => 'open',
                'thursday' => 'open',
                'friday' => 'open',
                'saturday' => 'open',
                'sunday' => 'open',
            ],
        ]);

        $startTime = Carbon::createFromTime(17); // 5pm
        $endTime = Carbon::createFromTime(23, 30); // 10:30pm

        for ($time = $startTime; $time->lessThan($endTime); $time->addMinutes(30)) {
            Schedule::create([
                'start_time' => $time->format('H:i:s'),
                'end_time' => $time->copy()->addMinutes(30)->format('H:i:s'),
                'restaurant_id' => $restaurant->id,
                'is_available' => true,
                'available_tables' => 100,
            ]);
        }

        RestaurantCreated::dispatch($restaurant);

        return $restaurant;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? self::getResource()::getUrl('index');
    }
}

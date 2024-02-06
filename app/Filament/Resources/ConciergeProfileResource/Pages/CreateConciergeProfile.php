<?php

namespace App\Filament\Resources\ConciergeProfileResource\Pages;

use App\Filament\Resources\ConciergeProfileResource;
use App\Models\User;
use App\Notifications\ConciergeCreated;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Str;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CreateConciergeProfile extends CreateRecord
{
    protected static string $resource = ConciergeProfileResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Registration')
                    ->description('This will be the login information for the concierge. The concierge will receive an email and sms with a link to set their password.')
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
                Section::make('Hotel Information')
                    ->description('This will be the hotel information for the concierge.')
                    ->icon('heroicon-m-building-office')
                    ->schema([
                        TextInput::make('hotel_name')
                            ->label('Hotel Name')
                            ->placeholder('Hotel Name')
                            ->required(),
                        PhoneInput::make('hotel_phone')
                            ->label('Hotel Phone Number')
                            ->onlyCountries(['US', 'CA'])
                            ->initialCountry('US')
                            ->required(),
                    ]),
                Section::make('Payout Information')
                    ->description('This will be the payout information for the concierge.')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        TextInput::make('payout_percentage')
                            ->label('Payout Percentage')
                            ->default(15)
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

        $user->assignRole('concierge');
        $user->notify(new ConciergeCreated($user));

        return $user->conciergeProfile()->create([
            'hotel_name' => $data['hotel_name'],
            'hotel_phone' => $data['hotel_phone'],
            'payout_percentage' => $data['payout_percentage'],
        ]);
    }
}

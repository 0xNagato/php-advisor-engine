<?php

namespace App\Filament\Resources\VenueResource\Components;

use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class VenueContactsForm
{
    public static function schema(): array
    {
        return [
            TextInput::make('contact_name')
                ->label('Contact Name')
                ->required(),
            PhoneInput::make('contact_phone')
                ->label('Contact Phone')
                ->required()
                ->onlyCountries(config('app.countries'))
                ->displayNumberFormat(PhoneInputNumberType::E164)
                ->disallowDropdown()
                ->validateFor(
                    country: config('app.countries'),
                    lenient: true,
                )
                ->initialCountry('US'),
            Checkbox::make('use_for_reservations')
                ->label('Use for Reservations')
                ->extraAttributes(['class' => 'text-indigo-600'])
                ->default(true),
            Fieldset::make('preferences')
                ->label('Notification Preferences')
                ->statePath('preferences')
                ->columns(1)
                ->schema([
                    Toggle::make('sms')
                        ->label('SMS')
                        ->rules([
                            fn (Get $get): Closure => static function (string $attribute, $value, Closure $fail) use ($get) {
                                if ($get('whatsapp') === false && $value === false) {
                                    $fail('SMS is required if Whatsapp is disabled.');
                                }
                            },
                        ])
                        ->inline(),
                    Toggle::make('whatsapp')
                        ->label('Whatsapp')
                        ->rules([
                            fn (Get $get): Closure => static function (string $attribute, $value, Closure $fail) use ($get) {
                                if ($get('sms') === false && $value === false) {
                                    $fail('Whatsapp is required if SMS is disabled.');
                                }
                            },
                        ])
                        ->inline(),
                ]),
        ];
    }
}

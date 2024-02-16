<?php

namespace App\Livewire;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CustomPersonalInfo extends PersonalInfo
{
    public array $only = ['first_name', 'last_name', 'email', 'phone'];

    protected function getProfileFormSchema(): array
    {
        $groupFields = Group::make([
            // $this->getNameComponent(),
            TextInput::make('first_name')
                ->required()
                ->label('First Name'),
            TextInput::make('last_name')
                ->required()
                ->label('Last Name'),
            $this->getEmailComponent(),
            PhoneInput::make('phone')
                ->label('Phone Number')
                ->onlyCountries(['US', 'CA'])
                ->initialCountry('US')
                ->required(),
        ])->columnSpan(2);

        return ($this->hasAvatars)
            ? [filament('filament-breezy')->getAvatarUploadComponent(), $groupFields]
            : [$groupFields];
    }
}

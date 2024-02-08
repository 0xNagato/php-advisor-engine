<?php

namespace App\Livewire;

use Filament\Forms\Components\Group;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CustomPersonalInfo extends PersonalInfo
{
    public array $only = ['name', 'email', 'phone'];

    protected function getProfileFormSchema(): array
    {
        $groupFields = Group::make([
            $this->getNameComponent(),
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

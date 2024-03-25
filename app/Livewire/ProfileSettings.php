<?php

namespace App\Livewire;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class ProfileSettings extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.profile-settings';

    public ?array $data = [];

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        $this->form->fill([
            'first_name' => auth()->user()->first_name,
            'last_name' => auth()->user()->last_name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone,
            'timezone' => auth()->user()->timezone ?? 'UTC',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('profile_photo_path')
                ->label('Profile Photo')
                ->disk('do')
                ->hidden(function () {
                    return ! auth()->user()->hasRole('concierge');
                }),
            TextInput::make('first_name')
                ->required()
                ->label('First Name'),
            TextInput::make('last_name')
                ->required()
                ->label('Last Name'),
            TextInput::make('email')
                ->required()
                ->label('Email')
                ->unique('users', ignorable: auth()->user())
                ->email(),
            PhoneInput::make('phone')
                ->required()
                ->label('Phone'),
            TimezoneSelect::make('timezone')
                ->searchable()
                ->selectablePlaceholder(false)
                ->required(),

        ])->statePath('data');
    }

    public function save(): void
    {
        $this->validate();

        auth()->user()->update($this->data);

        Notification::make()
            ->title('Profile updated successfully.')
            ->success()
            ->send();
    }
}

<?php

namespace App\Livewire;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class ProfileSettings extends Widget implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.profile-settings';

    public function mount(): void
    {
        $this->form->fill([
            'first_name' => auth()->user()->first_name,
            'last_name' => auth()->user()->last_name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
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
            TextInput::make('phone')
                ->required()
                ->label('Phone'),

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

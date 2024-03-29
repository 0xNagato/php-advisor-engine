<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Hash;

class PasswordSettings extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.password-settings';

    public ?array $data = [];

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        $this->form->fill([
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->hiddenLabel()
                    ->placeholder('Current Password')
                    ->required()
                    ->password()
                    ->rule('current_password'),
                TextInput::make('new_password')
                    ->hiddenLabel()
                    ->placeholder('New Password')
                    ->password()
                    ->minLength(8)
                    ->required(),
                TextInput::make('new_password_confirmation')
                    ->hiddenLabel()
                    ->placeholder('Confirm New Password')
                    ->password()
                    ->same('new_password')
                    ->required(),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = collect($this->form->getState())->only('new_password')->all();

        auth()->user()->update([
            'password' => Hash::make($data['new_password']),
        ]);

        session()->forget('password_hash_'.Filament::getCurrentPanel()->getAuthGuard());

        Filament::auth()->login($this->user);

        $this->reset(['data']);

        Notification::make()
            ->success()
            ->title(__('filament-breezy::default.profile.password.notify'))
            ->send();
    }
}

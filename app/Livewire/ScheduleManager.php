<?php

namespace App\Livewire;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

class ScheduleManager extends MyProfileComponent
{
    public array $data;
    
    protected string $view = 'livewire.time-slot-manager';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('restaurant');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
            ])
            ->statePath('data');
    }
}

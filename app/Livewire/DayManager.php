<?php

namespace App\Livewire;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

class DayManager extends MyProfileComponent
{
    public array $data;

    protected string $view = 'livewire.day-manager';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('restaurant');
    }

    public function mount(): void
    {
        // get restaurant for current user
        $restaurant = auth()->user()->restaurant;

        $days = $restaurant->open_days ?? [
            'monday' => 'open',
            'tuesday' => 'open',
            'wednesday' => 'open',
            'thursday' => 'open',
            'friday' => 'open',
            'saturday' => 'open',
            'sunday' => 'open',
        ];

        $this->form->fill([
            'monday' => $days['monday'] === 'open',
            'tuesday' => $days['tuesday'] === 'open',
            'wednesday' => $days['wednesday'] === 'open',
            'thursday' => $days['thursday'] === 'open',
            'friday' => $days['friday'] === 'open',
            'saturday' => $days['saturday'] === 'open',
            'sunday' => $days['sunday'] === 'open',
        ]);
    }

    public function form(Form $form): Form
    {
        $days = collect([
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ])->map(fn ($day) => Checkbox::make($day)
            ->label(ucfirst($day)));

        return $form
            ->schema($days->toArray())
            ->statePath('data');
    }

    public function submit(): void
    {
        // save the days back to the restaurant
        $restaurant = auth()->user()->restaurant;
        $restaurant->open_days = [
            'monday' => $this->data['monday'] ? 'open' : 'closed',
            'tuesday' => $this->data['tuesday'] ? 'open' : 'closed',
            'wednesday' => $this->data['wednesday'] ? 'open' : 'closed',
            'thursday' => $this->data['thursday'] ? 'open' : 'closed',
            'friday' => $this->data['friday'] ? 'open' : 'closed',
            'saturday' => $this->data['saturday'] ? 'open' : 'closed',
            'sunday' => $this->data['sunday'] ? 'open' : 'closed',
        ];

        $restaurant->save();

        Notification::make()
            ->title('Days updated successfully')
            ->success()
            ->send();
    }
}

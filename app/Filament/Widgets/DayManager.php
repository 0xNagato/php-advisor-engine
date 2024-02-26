<?php

namespace App\Filament\Widgets;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class DayManager extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.day-manager';

    public array $data;

    public static function canView(): bool
    {
        // return auth()->user()?->hasRole('restaurant');
        return false;
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
            ->statePath('data')
            ->columns(3);
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

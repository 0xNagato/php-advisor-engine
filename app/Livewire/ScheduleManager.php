<?php

namespace App\Livewire;

use App\Forms\Components\TimeSlot;
use App\Models\Schedule;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

class ScheduleManager extends MyProfileComponent
{
    public array $data;

    protected string $view = 'livewire.schedule-manager';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('restaurant');
    }

    public function mount(): void
    {
        $schedules = Schedule::where('restaurant_id', auth()->user()->restaurant->id)
            ->orderBy('start_time')
            ->get();

        $this->form->fill([
            'schedules' => $schedules->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('schedules')
                    ->hiddenLabel()
                    ->addable(false)
                    ->reorderable(false)
                    ->deletable(false)
                    ->simple(
                        TimeSlot::make('schedule')
                    )
            ])
            ->statePath('data');
    }
}

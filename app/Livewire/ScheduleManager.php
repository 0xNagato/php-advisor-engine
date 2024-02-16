<?php

namespace App\Livewire;

use App\Forms\Components\TimeSlot;
use App\Models\Schedule;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
                    ),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->validate();

        $schedules = collect($this->data['schedules'])
            ->map(function ($schedule) {
                return [
                    'restaurant_id' => auth()->user()->restaurant->id,
                    'start_time' => $schedule['schedule']['start_time'],
                    'end_time' => $schedule['schedule']['end_time'],
                    'is_available' => $schedule['schedule']['is_available'],
                    'available_tables' => $schedule['schedule']['available_tables'],
                ];
            });

        foreach ($schedules as $schedule) {
            Schedule::updateOrCreate(
                ['restaurant_id' => $schedule['restaurant_id'], 'start_time' => $schedule['start_time']],
                ['end_time' => $schedule['end_time'], 'is_available' => $schedule['is_available'], 'available_tables' => $schedule['available_tables']]
            );
        }

        Notification::make()
            ->title('Times updated successfully')
            ->success()
            ->send();
    }
}

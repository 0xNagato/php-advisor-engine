<?php

namespace App\Filament\Widgets;

use App\Forms\Components\TimeSlot;
use App\Models\Schedule;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class ScheduleManager extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.schedule-manager';

    public array $data;

    protected string|int|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('restaurant') && request()?->route()?->getName() === 'filament.admin.pages.my-restaurant';
    }

    public function mount(): void
    {
        $schedules = Schedule::where('restaurant_id', auth()->user()->restaurant->id)
            ->orderBy('start_time')
            ->get();

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

        $days_closed = collect($days)
            ->filter(fn ($day) => $day === 'closed')
            ->keys()
            ->toArray();

        $this->form->fill([
            'schedules' => $schedules->toArray(),
            'days_closed' => $days_closed,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('days_closed')
                    ->hiddenLabel()
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ])
                    ->multiple()
                    ->label('Days Unavailable')
                    ->placeholder('Select days closed')
                    ->helperText('Please select any days which are unavailable for bookings'),

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
        $days = [
            'monday' => in_array('monday', $this->data['days_closed'], true) ? 'closed' : 'open',
            'tuesday' => in_array('tuesday', $this->data['days_closed'], true) ? 'closed' : 'open',
            'wednesday' => in_array('wednesday', $this->data['days_closed'], true) ? 'closed' : 'open',
            'thursday' => in_array('thursday', $this->data['days_closed'], true) ? 'closed' : 'open',
            'friday' => in_array('friday', $this->data['days_closed'], true) ? 'closed' : 'open',
            'saturday' => in_array('saturday', $this->data['days_closed'], true) ? 'closed' : 'open',
            'sunday' => in_array('sunday', $this->data['days_closed'], true) ? 'closed' : 'open',
        ];

        $restaurant = auth()->user()->restaurant;
        $restaurant->open_days = $days;
        $restaurant->save();

        collect($this->data['schedules'])
            ->map(fn (array $data) => $data['schedule'])
            ->each(fn (array $schedule) => Schedule::find($schedule['id'])
                ?->update(['is_available' => $schedule['is_available'], 'available_tables' => $schedule['available_tables']])
            );

        Notification::make()
            ->title('Schedule updated successfully')
            ->success()
            ->send();
    }
}

<?php

namespace App\Livewire;

use App\Models\Schedule;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class ScheduleManager extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.schedule-manager';
    protected static bool $isLazy = true;

    public array $data;

    protected ?Collection $schedules;

    protected string|int|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('restaurant') && request()?->route()?->getName() === 'filament.admin.pages.my-restaurant';
    }

    public function mount(): void
    {
        $schedules = Schedule::where('restaurant_id', auth()->user()->restaurant->id)
            ->where('is_available', false)
            ->orderBy('start_time')
            ->get()
            ->map(fn($schedule) => date('g:i a', strtotime($schedule->start_time)))
            ->toArray();

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
            ->filter(fn($day) => $day === 'closed')
            ->keys()
            ->toArray();

        $this->form->fill([
            'schedules' => $schedules,
            'days_closed' => $days_closed,
        ]);
    }

    public function form(Form $form): Form
    {
        $schedules = Schedule::where('restaurant_id', auth()->user()->restaurant->id)
            ->where('is_available', true)
            ->orderBy('start_time')
            ->get()
            ->mapWithKeys(fn($schedule) => [date('g:i a', strtotime($schedule->start_time)) => date('g:i a', strtotime($schedule->start_time))]);

        return $form
            ->schema([
                Select::make('days_closed')
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
                    ->placeholder('Select days closed')
                    ->label('Select any days which are unavailable for bookings'),

                Select::make('schedules')
                    ->options($schedules)
                    ->multiple()
                    ->placeholder('Select unavailable times')
                    ->label('Select any times which are unavailable for bookings'),
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

        $schedulesData = collect($this->data['schedules'])
            ->map(fn($time) => date('H:i:s', strtotime($time)))
            ->toArray();

        $schedules = Schedule::where('restaurant_id', auth()->user()->restaurant->id)
            ->where(function ($query) use ($schedulesData) {
                foreach ($schedulesData as $time) {
                    $query->orWhere('start_time', $time);
                }
            })
            ->get();

        $schedules->each(fn($schedule) => $schedule->update(['is_available' => 0]));

        // Get all the schedules for the restaurant that are currently marked as unavailable.
        $unavailableSchedules = Schedule::where('restaurant_id', auth()->user()->restaurant->id)
            ->where('is_available', false)
            ->get()
            ->map(fn($schedule) => date('H:i:s', strtotime($schedule->start_time)))
            ->toArray();

        // Convert the times in the $this->data['schedules'] array back to the 'H:i:s' format.
        $submittedSchedules = collect($this->data['schedules'])
            ->map(fn($time) => date('H:i:s', strtotime($time)))
            ->toArray();

        // Find the schedules that were originally unavailable but were removed from the list.
        $schedulesToMakeAvailable = array_diff($unavailableSchedules, $submittedSchedules);

        // Update these schedules as available again.
        Schedule::where('restaurant_id', auth()->user()->restaurant->id)
            ->whereIn('start_time', $schedulesToMakeAvailable)
            ->update(['is_available' => true]);

        Notification::make()
            ->title('Schedule updated successfully')
            ->success()
            ->send();
    }
}

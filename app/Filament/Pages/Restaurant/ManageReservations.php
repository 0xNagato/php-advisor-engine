<?php

namespace App\Filament\Pages\Restaurant;

use App\Forms\Components\TimeRange;
use App\Models\Reservation;
use Carbon\Carbon;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageReservations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.pages.restaurant.manage-reservations';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['restaurant']);
    }

    public function mount(): void
    {
        $dates = collect(range(0, 6))->map(function ($day) {
            $date = now()->addDays($day);

            $reservation = Reservation::where('date', $date->format('Y-m-d'))
                ->where('restaurant_profile_id', auth()->user()->restaurantProfile->id)
                ->first();

            if ($reservation) {
                return [
                    'date' => $reservation->date,
                    'startTime' => $reservation->start_time,
                    'endTime' => $reservation->end_time,
                    'closed' => $reservation->is_closed,
                ];
            }

            return [
                'date' => $date->format('Y-m-d'),
                'startTime' => '18:00',
                'endTime' => '23:00',
                'closed' => false,
            ];
        });

        clock($dates);

        $this->form->fill([
            'dates' => $dates,
        ]);
    }

    public function form(Form $form): Form
    {
        $section = Section::make('Available Times')
            ->description('Set the times your restaurant is available for reservations.')
            ->schema([
                Repeater::make('dates')
                    ->hiddenLabel()
                    ->deletable(false)
                    ->reorderable(false)
                    ->addActionLabel('Add New Date')
                    ->itemLabel(fn(array $state): string => Carbon::make($state['date'])?->format('l, F j'))
                    ->simple(
                        TimeRange::make('day')
                            ->hiddenLabel()
                            ->required()
                    ),
            ]);

        return $form
            ->schema([
                $section,
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->data['dates'] as $date) {
            $data = $date['day'];

            Reservation::updateOrCreate(
                ['date' => $data['date'], 'restaurant_profile_id' => auth()->user()->restaurantProfile->id],
                [
                    'start_time' => $data['startTime'],
                    'end_time' => $data['endTime'],
                    'is_closed' => $data['closed'],
                ]
            );
        }
    }
}

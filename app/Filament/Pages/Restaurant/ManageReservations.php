<?php

namespace App\Filament\Pages\Restaurant;

use App\Forms\Components\TimeRange;
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

            return [
                'date' => $date->format('Y-m-d'),
                'startTime' => '18:00',
                'endTime' => '23:00',
                'closed' => false,
            ];
        });

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
                    ->itemLabel(fn (array $state): string => Carbon::make($state['date'])?->format('l, F j'))
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
    }
}

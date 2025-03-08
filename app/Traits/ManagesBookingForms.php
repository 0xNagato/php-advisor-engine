<?php

namespace App\Traits;

use App\Actions\Reservations\GetReservationTimeOptions;
use App\Models\Cuisine;
use App\Services\ReservationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait ManagesBookingForms
{
    public const int AVAILABILITY_DAYS = 3;

    public const int MINUTES_PAST = 35;

    public const int MINUTES_FUTURE = 120;

    public const int MAX_DAYS_IN_ADVANCE = 30;

    public string $timezone;

    public bool $advanced = false;

    public ?Collection $neighborhoods = null;

    protected function commonFormComponents(): array
    {
        return [
            Hidden::make('date')
                ->default(now($this->timezone)->format('Y-m-d')),
            ToggleButtons::make('radio_date')
                ->options([
                    now($this->timezone)->format('Y-m-d') => 'Today',
                    now($this->timezone)->addDay()->format('Y-m-d') => 'Tomorrow',
                    'select_date' => 'Select Date',
                ])
                ->grouped()
                ->columnSpanFull()
                ->afterStateUpdated(function ($state, $set) {
                    if ($state !== 'select_date') {
                        $set('date', $state);
                    }
                })
                ->default(now($this->timezone)->format('Y-m-d'))
                ->inline()
                ->hiddenLabel()
                ->live()
                ->columnSpanFull()
                ->required(),
            Select::make('neighborhood')
                ->prefixIcon('ri-community-line')
                ->searchable()
                ->options(fn () => $this->neighborhoods)
                ->placeholder('Neighborhood')
                ->hiddenLabel()
                ->columnSpanFull()
                ->live()
                ->visible($this->advanced),
            $this->getCuisineInput(),
            DatePicker::make('select_date')
                ->hiddenLabel()
                ->live()
                ->columnSpanFull()
                ->weekStartsOnSunday()
                ->default(now($this->timezone)->format('Y-m-d'))
                ->minDate(now($this->timezone)->format('Y-m-d'))
                ->maxDate(now($this->timezone)->addDays(self::MAX_DAYS_IN_ADVANCE)->format('Y-m-d'))
                ->hidden(fn (Get $get) => $get('radio_date') !== 'select_date')
                ->afterStateUpdated(fn ($state, $set) => $set('date', Carbon::parse($state)->format('Y-m-d')))
                ->prefixIcon('heroicon-m-calendar')
                ->native(isPrimaApp())
                ->closeOnDateSelection(),
            $this->getGuestCountInput(),
            Select::make('reservation_time')
                ->prefixIcon('heroicon-m-clock')
                ->options(fn (Get $get) => GetReservationTimeOptions::run(date: $get('date')))
                ->disableOptionWhen(function (Get $get, $value) {
                    $isCurrentDay = $get('date') === now($this->timezone)->format('Y-m-d');
                    if (! $isCurrentDay) {
                        return false;
                    }

                    $currentTime = now($this->timezone);
                    $optionTime = Carbon::createFromFormat('H:i:s', $value, $this->timezone);

                    // Only allow times that are at least MINUTES_PAST minutes in the future
                    return $optionTime->isBefore($currentTime->copy()->addMinutes(ReservationService::MINUTES_PAST));
                })
                ->placeholder('Select Time')
                ->hiddenLabel()
                ->required()
                ->columnSpan(1)
                ->live(),
        ];
    }

    protected function getGuestCountInput(): Select
    {
        return Select::make('guest_count')
            ->prefixIcon('heroicon-m-users')
            ->options([
                2 => '2 Guests',
                3 => '3 Guests',
                4 => '4 Guests',
                5 => '5 Guests',
                6 => '6 Guests',
                7 => '7 Guests',
                8 => '8 Guests',
            ])
            ->placeholder('Party Size')
            ->live()
            ->hiddenLabel()
            ->columnSpan(1)
            ->required();
    }

    protected function getCuisineInput(): Select
    {
        $cuisines = Cuisine::all()->groupBy('group')
            ->mapWithKeys(fn ($items, $group) => [$group => $items->pluck('name', 'id')->toArray()]
            )->toArray();

        return Select::make('cuisine')
            ->prefixIcon('phosphor-bowl-steam-bold')
            ->label('Cuisine')
            ->options($cuisines)
            ->searchable()
            ->placeholder('Cuisine')
            ->hiddenLabel()
            ->multiple()
            ->columnSpanFull()
            ->live()
            ->visible($this->advanced);
    }
}

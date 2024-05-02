<?php

namespace App\Traits;

use App\Models\Restaurant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Illuminate\Support\Carbon;

trait ManagesBookingForms
{
    public const int AVAILABILITY_DAYS = 3;

    public const int MINUTES_PAST = 30;

    public const int MINUTES_FUTURE = 60;

    protected function commonFormComponents(): array
    {
        return [
            Hidden::make('date')
                ->default(now(auth()->user()->timezone)->format('Y-m-d')),
            Radio::make('radio_date')
                ->options([
                    now(auth()->user()->timezone)->format('Y-m-d') => 'Today',
                    now(auth()->user()->timezone)->addDay()->format('Y-m-d') => 'Tomorrow',
                    'select_date' => 'Select Date',
                ])
                ->afterStateUpdated(function ($state, $set) {
                    if ($state !== 'select_date') {
                        $set('date', $state);
                    }
                })
                ->default(now(auth()->user()->timezone)->format('Y-m-d'))
                ->inline()
                ->hiddenLabel()
                ->live()
                ->columnSpanFull()
                ->required(),
            DatePicker::make('select_date')
                ->hiddenLabel()
                ->live()
                ->columnSpanFull()
                ->weekStartsOnSunday()
                ->default(now(auth()->user()->timezone)->format('Y-m-d'))
                ->minDate(now(auth()->user()->timezone)->format('Y-m-d'))
                ->maxDate(now(auth()->user()->timezone)->addMonth()->format('Y-m-d'))
                ->hidden(function (Get $get) {
                    return $get('radio_date') !== 'select_date';
                })
                ->afterStateUpdated(fn ($state, $set) => $set('date', Carbon::parse($state)->format('Y-m-d')))
                ->prefixIcon('heroicon-m-calendar')
                ->native(false)
                ->closeOnDateSelection(),
            Select::make('guest_count')
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
                ->required(),
            Select::make('reservation_time')
                ->prefixIcon('heroicon-m-clock')
                ->options(function (Get $get) {
                    return $this->getReservationTimeOptions($get('date'));
                })
                ->disableOptionWhen(function (Get $get, $value) {
                    $isCurrentDay = $get('date') === now(auth()->user()->timezone)->format('Y-m-d');

                    return $isCurrentDay && $value < now(auth()->user()->timezone)->format('H:i:s');
                })
                ->placeholder('Select Time')
                ->hiddenLabel()
                ->required()
                ->columnSpan(1)
                ->live(),
        ];
    }

    protected function getReservationTimeOptions(string $date, $onlyShowFuture = false): array
    {
        $userTimezone = auth()->user()->timezone;
        $currentDate = ($date === Carbon::now($userTimezone)->format('Y-m-d'));

        $currentTime = Carbon::now($userTimezone);
        $startTime = Carbon::createFromTime(Restaurant::DEFAULT_START_HOUR, 0, 0, $userTimezone);
        $endTime = Carbon::createFromTime(Restaurant::DEFAULT_END_HOUR, 0, 0, $userTimezone);

        $reservationTimes = [];

        for ($time = $startTime; $time->lte($endTime); $time->addMinutes(30)) {
            if ($onlyShowFuture && $currentDate && $time->lt($currentTime)) {
                continue;
            }
            $reservationTimes[$time->format('H:i:s')] = $time->format('g:i A');
        }

        return $reservationTimes;
    }
}

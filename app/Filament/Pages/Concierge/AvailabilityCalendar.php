<?php

namespace App\Filament\Pages\Concierge;

use App\Models\Restaurant;
use App\Models\Schedule;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class AvailabilityCalendar extends Page
{

    public const int AVAILABILITY_DAYS = 3;

    public const int MINUTES_PAST = 30;

    public const int MINUTES_FUTURE = 60;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.pages.concierge.availability-calendar';

    protected static ?int $navigationSort = -2;

    public ?array $data;

    public ?array $resraurants;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('concierge');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
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
                ->afterStateUpdated(fn($state, $set) => $set('date', Carbon::parse($state)->format('Y-m-d')))
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
        ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('data');
    }

    public function getReservationTimeOptions(string $date, $onlyShowFuture = false): array
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

    public function updatedData($data, $key): void
    {
        if ($key === 'radio_date' && $data === 'select_date') {
            return;
        }

        if ($key === 'guest_count' && empty($data)) {
            $this->data['guest_count'] = 2;
        }

        if (isset($this->data['reservation_time'], $this->data['date'], $this->data['guest_count'])) {
            $userTimezone = auth()->user()->timezone;
            $requestedDate = Carbon::createFromFormat('Y-m-d', $this->data['date'], $userTimezone);
            $currentDate = Carbon::now($userTimezone);

            if (($key === 'radio_date' || $key === 'select_date') && $currentDate->isSameDay($requestedDate)) {

                $reservationTime = Carbon::createFromFormat('H:i:s', $this->form->getState()['reservation_time'], $userTimezone);
                $currentTime = Carbon::now($userTimezone);

                // Check if the reservation time is before the current time
                if ($reservationTime->lt($currentTime)) {
                    $this->schedules = null;
                    return;
                }
            }

            if ($currentDate->isSameDay($requestedDate)) {
                $reservationTime = Carbon::createFromFormat('H:i:s', $this->form->getState()['reservation_time'], $userTimezone);
                $currentTime = Carbon::now($userTimezone);

                if ($reservationTime->copy()->subMinutes(self::MINUTES_PAST)->gt($currentTime)) {
                    $reservationTime = $reservationTime->subMinutes(self::MINUTES_PAST)->format('H:i:s');
                } else {
                    $reservationTime = $this->form->getState()['reservation_time'];
                }
            } else {
                $reservationTime = $this->form->getState()['reservation_time'];
            }

            $endTime = Carbon::createFromFormat('H:i:s', $reservationTime, $userTimezone)->addMinutes(self::MINUTES_FUTURE);
            $limitTime = Carbon::createFromTime(23, 59, 0, $userTimezone);

            if ($endTime->gt($limitTime)) {
                $endTimeForQuery = '23:59:59';
            } else {
                $endTimeForQuery = $endTime->format('H:i:s');
            }

            $guestCount = $this->form->getState()['guest_count'];
            $guestCount = ceil($guestCount);
            if ($guestCount % 2 !== 0) {
                $guestCount++;
            }

            $restaurants = Restaurant::all();

            foreach ($restaurants as $restaurant) {
                $this->ensureOrGenerateSchedules($restaurant->id, $requestedDate);
                $this->resraurants[$restaurant->id]['restaurant'] = $restaurant;
                $this->resraurants[$restaurant->id]['schedules'] =
                    Schedule::where('restaurant_id', $restaurant->id)
                        ->where('booking_date', $this->form->getState()['date'])
                        ->where('party_size', $guestCount)
                        ->where('start_time', '>=', $reservationTime)
                        ->where('start_time', '<=', $endTimeForQuery)
                        ->get();
            }
        }
    }

    public function ensureOrGenerateSchedules(int $restaurantId, Carbon $date): void
    {
        $scheduleExists = Schedule::where('restaurant_id', $restaurantId)
            ->where('booking_date', $date->format('Y-m-d'))
            ->exists();

        if (!$scheduleExists) {
            $restaurant = Restaurant::find($restaurantId);
            $restaurant?->generateScheduleForDate($date);
        }
    }

    /**
     * @throws Exception
     */
    public function createBooking($scheduleId): void
    {
        $this->redirectRoute('filament.admin.pages.concierge.reservation-hub', [
            'scheduleId' => $scheduleId,
        ]);
    }
}

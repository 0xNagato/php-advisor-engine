<?php

namespace App\Livewire\Booking;

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Restaurant;
use App\Models\Schedule;
use App\Services\BookingService;
use App\Services\SalesTaxService;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use chillerlan\QRCode\QRCode;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Stripe\Exception\ApiErrorException;

/**
 * @property Form $form
 */
class BookingWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    public const int AVAILABILITY_DAYS = 3;

    public const int MINUTES_PAST = 30;

    public const int MINUTES_FUTURE = 60;

    protected static string $view = 'filament.widgets.booking-widget';

    protected static bool $isLazy = false;

    #[Session]
    public ?string $qrCode;

    #[Session]
    public ?string $bookingUrl;

    #[Session]
    public ?Booking $booking;

    public bool $isLoading = false;

    public bool $paymentSuccess = false;

    public bool $SMSSent = false;

    public ?array $data = [];

    /**
     * @var Collection<Schedule>|Collection
     */
    public Collection $schedulesToday;

    /**
     * @var Collection<Schedule>|Collection
     */
    public Collection $schedulesThisWeek;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->form->fill();

        /** @var Collection<Schedule> $emptyCollection */
        $emptyCollection = new Collection();

        $this->schedulesToday = $emptyCollection;
        $this->schedulesThisWeek = $emptyCollection;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->disableOptionWhen(function ($value) {
                        return $value < now(auth()->user()->timezone)->format('H:i:s');
                    })
                    ->placeholder('Select Time')
                    ->hiddenLabel()
                    ->required()
                    ->columnSpan(1)
                    ->live(),
                Select::make('restaurant')
                    ->prefixIcon('heroicon-m-building-storefront')
                    ->options(
                        Restaurant::available()->pluck('restaurant_name', 'id')
                    )
                    ->placeholder('Select Restaurant')
                    ->required()
                    ->live()
                    ->hiddenLabel()
                    ->searchable()
                    ->columnSpanFull()
                    ->selectablePlaceholder(false),
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

        if (isset($this->data['restaurant'], $this->data['reservation_time'], $this->data['date'], $this->data['guest_count'])) {
            $userTimezone = auth()->user()->timezone;
            $requestedDate = Carbon::createFromFormat('Y-m-d', $this->data['date'], $userTimezone);
            $currentDate = Carbon::now($userTimezone);

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

            $restaurantId = $this->form->getState()['restaurant'];

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

            $this->ensureOrGenerateSchedules($restaurantId, $requestedDate);

            $this->schedulesToday = Schedule::where('restaurant_id', $restaurantId)
                ->where('booking_date', $this->form->getState()['date'])
                ->where('party_size', $guestCount)
                ->where('start_time', '>=', $reservationTime)
                ->where('start_time', '<=', $endTimeForQuery)
                ->get();

            if ($requestedDate->isSameDay($currentDate)) {
                for ($i = 1; $i <= self::AVAILABILITY_DAYS; $i++) {
                    $newDate = $requestedDate->copy()->addDays($i);
                    $this->ensureOrGenerateSchedules($restaurantId, $newDate);
                }
                $this->schedulesThisWeek = Schedule::where('restaurant_id', $restaurantId)
                    ->where('start_time', $this->form->getState()['reservation_time'])
                    ->where('party_size', $guestCount)
                    ->whereDate('booking_date', '>', $currentDate)
                    ->whereDate('booking_date', '<=', $currentDate->addDays(self::AVAILABILITY_DAYS))
                    ->get();
            } else {
                $this->schedulesThisWeek = new Collection();
            }
        }
    }

    public function ensureOrGenerateSchedules(int $restaurantId, Carbon $date): void
    {
        $scheduleExists = Schedule::where('restaurant_id', $restaurantId)
            ->where('booking_date', $date->format('Y-m-d'))
            ->exists();

        if (! $scheduleExists) {
            $restaurant = Restaurant::find($restaurantId);
            if ($restaurant) {
                $restaurant->generateScheduleForDate($date);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function createBooking($scheduleId, ?string $date = null): void
    {
        $data = $this->form->getState();
        $schedule = Schedule::find($scheduleId);

        $data['date'] = $date ?? $data['date'];

        $bookingAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $data['date'].' '.$schedule->start_time,
            auth()->user()->timezone
        );

        $this->booking = Booking::create([
            'schedule_id' => $scheduleId,
            'guest_count' => $data['guest_count'],
            'concierge_id' => auth()->user()->concierge->id,
            'status' => BookingStatus::PENDING,
            'booking_at' => $bookingAt,
        ]);

        $taxData = app(SalesTaxService::class)->calculateTax(
            'miami',
            $this->booking->total_fee
        );
        $totalWithTaxInCents =
            $this->booking->total_fee + $taxData->amountInCents;

        $this->booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->city,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
        ]);

        $shortUrlQr = ShortURL::destinationUrl(
            route('bookings.create', [
                'token' => $this->booking->uuid,
                'r' => 'qr',
            ])
        )->make();

        $shortUrl = ShortURL::destinationUrl(
            route('bookings.create', [
                'token' => $this->booking->uuid,
                'r' => 'sms',
            ])
        )->make();

        $this->bookingUrl = $shortUrl->default_short_url;

        $this->qrCode = (new QRCode())->render($shortUrlQr->default_short_url);

        BookingCreated::dispatch($this->booking);
    }

    /**
     * @throws ApiErrorException
     */
    public function completeBooking($form): void
    {
        app(BookingService::class)->processBooking($this->booking, $form);

        $this->booking->update(['concierge_referral_type' => 'concierge']);

        $this->isLoading = false;
        $this->paymentSuccess = true;
    }

    public function cancelBooking(): void
    {
        $this->booking->update(['status' => 'cancelled']);
        BookingCancelled::dispatch($this->booking);
        $this->booking = null;
        $this->qrCode = null;
        $this->bookingUrl = null;

        // $this->form->fill();
        // $this->schedulesThisWeek = new Collection();
        // $this->schedulesToday = new Collection();
    }

    public function resetBooking(): void
    {
        $this->booking = null;
        $this->qrCode = null;
        $this->bookingUrl = null;
        $this->paymentSuccess = false;
        $this->SMSSent = false;

        $this->form->fill();
        $this->schedulesThisWeek = new Collection([new Schedule()]);
        $this->schedulesToday = new Collection([new Schedule()]);
    }

    #[On('sms-sent')]
    public function SMSSent(): void
    {
        $this->SMSSent = true;
    }
}

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

    public const int AVAILABILITY_HOURS = 3;

    public const int AVAILABILITY_DAYS = 4;

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
     * @var Collection<Schedule>
     */
    public Collection $schedulesToday;

    /**
     * @var Collection<Schedule>
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
                Radio::make('date')
                    ->options(function ($get) {
                        return [
                            now(auth()->user()->timezone)->format('Y-m-d') => 'Today',
                            now(auth()->user()->timezone)->addDay()->format('Y-m-d') => 'Tomorrow',
                            $get('date_selected') => 'Select Date',
                        ];
                    })
                    ->default(now(auth()->user()->timezone)->format('Y-m-d'))
                    ->inline()
                    ->hiddenLabel()
                    ->live()
                    ->columnSpanFull()
                    ->required(),
                DatePicker::make('date_selected')
                    ->hiddenLabel()
                    ->live()
                    ->columnSpanFull()
                    ->default(now(auth()->user()->timezone)->addDays(2)->format('Y-m-d'))
                    ->minDate(now(auth()->user()->timezone)->addDay()->format('Y-m-d'))
                    ->hidden(fn(Get $get) => Carbon::parse($get('date'), auth()->user()->timezone)->lte(now(auth()->user()->timezone)))
                    ->afterStateUpdated(fn($state, $set) => $set('date', $state)),
                Select::make('guest_count')
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
                    ->options(function (Get $get) {
                        return $this->getReservationTimeOptions($get('date'));
                    })
                    ->placeholder('Select a time')
                    ->hiddenLabel()
                    ->required()
                    ->columnSpan(1)
                    ->live(),
                Select::make('restaurant')
                    ->options(
                        Restaurant::available()->pluck('restaurant_name', 'id')
                    )
                    ->placeholder('Select a restaurant')
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

    public function getReservationTimeOptions(string $date): array
    {
        $userTimezone = auth()->user()->timezone;
        $currentDate = (bool)($date === Carbon::now($userTimezone)->format('Y-m-d'));

        $currentTime = Carbon::now($userTimezone);
        $startTime = Carbon::createFromTime(12, 0, 0, $userTimezone);
        $endTime = Carbon::createFromTime(22, 0, 0, $userTimezone);

        $reservationTimes = [];

        for ($time = $startTime; $time->lte($endTime); $time->addMinutes(30)) {
            if ($currentDate && $time->lt($currentTime)) {
                continue;
            }
            $reservationTimes[$time->format('H:i:s')] = $time->format('g:i A');
        }

        return $reservationTimes;
    }

    public function updatedData($data, $key): void
    {
        if ($key === 'restaurant' || ($key === 'reservation_time' && isset($this->data['restaurant'])) || (isset($this->data['restaurant'], $this->data['reservation_time']) && $key === 'date') || (isset($this->data['restaurant'], $this->data['reservation_time'], $this->data['date']) && $key === 'guest_count')) {
            $userTimezone = auth()->user()->timezone;
            $currentDate = (bool)($this->data['date'] === Carbon::now($userTimezone)->format('Y-m-d'));

            if (!$currentDate) {
                $reservationTime = $this->form->getState()['reservation_time'];
            } else {
                $reservationTime = Carbon::createFromFormat('H:i:s', $this->form->getState()['reservation_time'], $userTimezone);
                if ($reservationTime->lt(Carbon::now($userTimezone))) {
                    $reservationTime = Carbon::now($userTimezone)->addHour();
                }
                $minute = $reservationTime->minute;
                $second = $reservationTime->second;

                if ($second > 0 || $minute % 30 > 0) {
                    $reservationTime = $reservationTime->subMinutes($minute % 30)->second(0);
                }

                $reservationTime = $reservationTime->format('H:i:s');
            }

            $restaurantId = $this->form->getState()['restaurant'];

            $endTime = Carbon::createFromFormat('H:i:s', $reservationTime, $userTimezone)->addHours(self::AVAILABILITY_HOURS)->subMinutes(30);
            $limitTime = Carbon::createFromTime(23, 59, 0, $userTimezone);

            if ($endTime->gt($limitTime)) {
                $endTimeForQuery = '23:59:59';
            } else {
                $endTimeForQuery = $endTime->format('H:i:s');
            }

            $this->schedulesToday = Schedule::where('restaurant_id', $restaurantId)
                ->where('start_time', '>=', $reservationTime)
                ->where('start_time', '<=', $endTimeForQuery)
                ->where('day_of_week', strtolower(Carbon::createFromFormat('Y-m-d', $this->form->getState()['date'], $userTimezone)->format('l')))
                ->get();

            $daysOfWeek = collect(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
            $currentDayIndex = $daysOfWeek->search(strtolower(Carbon::now($userTimezone)->format('l')));

            $sortedDaysOfWeek = $daysOfWeek->slice($currentDayIndex)->concat($daysOfWeek->slice(0, $currentDayIndex));

            $this->schedulesThisWeek = Schedule::where('restaurant_id', $restaurantId)
                ->where('start_time', $this->form->getState()['reservation_time'])
                ->whereIn('day_of_week', collect(range(1, self::AVAILABILITY_DAYS))->map(fn($day) => strtolower(Carbon::now($userTimezone)->addDays($day)->format('l')))->toArray())
                ->get()
                ->sortBy(fn($schedule) => $sortedDaysOfWeek->search($schedule->day_of_week));
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
            $data['date'] . ' ' . $schedule->start_time,
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
        $this->schedulesThisWeek = new Collection();
        $this->schedulesToday = new Collection();
    }

    #[On('sms-sent')]
    public function SMSSent(): void
    {
        $this->SMSSent = true;
    }
}

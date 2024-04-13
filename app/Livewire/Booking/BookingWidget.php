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

    public const AVAILABILITY_HOURS = 3;

    public const AVAILABILITY_DAYS = 4;

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

    public static function canView(): bool
    {
        return auth()->user()->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->form->fill();
        $this->schedulesToday = new Collection();
        $this->schedulesThisWeek = new Collection();
    }

    public function form(Form $form): Form
    {
        $reservationTimes = [];
        $start = Carbon::createFromTime(12);
        $end = Carbon::createFromTime(22);

        for ($time = $start; $time->lte($end); $time->addMinutes(30)) {
            $dbTime = $time->format('H:i:s');
            $displayTime = $time->format('g:i A');
            $reservationTimes[$dbTime] = $displayTime;
        }

        return $form
            ->schema([
                Radio::make('date')
                    ->options(function ($get) {
                        return [
                            now()->format('Y-m-d') => 'Today',
                            now()->addDay()->format('Y-m-d') => 'Tomorrow',
                            $get('date_selected') => 'Select Date',
                        ];
                    })
                    ->default(now()->format('Y-m-d'))
                    ->inline()
                    ->hiddenLabel()
                    ->live()
                    ->required(),
                DatePicker::make('date_selected')
                    ->hiddenLabel()
                    ->live()
                    ->default(now()->addDays(2)->format('Y-m-d'))
                    ->minDate(now()->addDay()->format('Y-m-d'))
                    ->hidden(fn (Get $get) => Carbon::parse($get('date'))->lte(now()->addDay()))
                    ->afterStateUpdated(fn ($state, $set) => $set('date', $state)),
                Select::make('guest_count')
                    ->options([
                        2 => '2 Guests ($200)',
                        3 => '3 Guests ($250)',
                        4 => '4 Guests ($300)',
                        5 => '5 Guests ($350)',
                        6 => '6 Guests ($400)',
                        7 => '7 Guests ($450)',
                        8 => '8 Guests ($500)',
                    ])
                    ->placeholder('Select number of guests')
                    ->live()
                    ->hiddenLabel()
                    ->required(),
                Select::make('reservation_time')
                    ->options($reservationTimes)
                    ->placeholder('Select a time')
                    ->hiddenLabel()
                    ->required()
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
                    ->selectablePlaceholder(false),
            ])
            ->statePath('data');
    }

    public function updatedData($data, $key): void
    {
        if ($key === 'restaurant' || ($key === 'reservation_time' && isset($this->data['restaurant'])) || ($key === 'date' && isset($this->data['restaurant']) && isset($this->data['reservation_time']))) {
            $reservationTime = $this->form->getState()['reservation_time'];
            $restaurantId = $this->form->getState()['restaurant'];

            $this->schedulesToday = Schedule::where('restaurant_id', $restaurantId)
                ->where('start_time', '>=', $reservationTime)
                ->where('start_time', '<=', Carbon::createFromFormat('H:i:s', $reservationTime)->addHours(self::AVAILABILITY_HOURS)->format('H:i:s'))
                ->where('day_of_week', Carbon::createFromFormat('Y-m-d', $this->form->getState()['date'])->format('l'))
                ->get();

            $daysOfWeek = collect(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
            $currentDayIndex = $daysOfWeek->search(strtolower(Carbon::now()->format('l')));

            $sortedDaysOfWeek = $daysOfWeek->slice($currentDayIndex)->concat($daysOfWeek->slice(0, $currentDayIndex));

            $this->schedulesThisWeek = Schedule::where('restaurant_id', $restaurantId)
                ->where('start_time', $reservationTime)
                ->whereIn('day_of_week', collect(range(1, self::AVAILABILITY_DAYS))->map(function ($day) {
                    return strtolower(Carbon::now()->addDays($day)->format('l'));
                })->toArray())
                ->get()
                ->sortBy(function ($schedule) use ($sortedDaysOfWeek) {
                    return $sortedDaysOfWeek->search($schedule->day_of_week);
                });
        }
    }

    /**
     * @throws Exception
     */
    public function createBooking($scheduleId, ?string $date): void
    {
        $data = $this->form->getState();

        $data['date'] = $date ?? $data['date'];

        $bookingAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $data['date'].' '.$data['reservation_time']
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

        $this->form->fill();
        $this->schedulesThisWeek = new Collection();
        $this->schedulesToday = new Collection();
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

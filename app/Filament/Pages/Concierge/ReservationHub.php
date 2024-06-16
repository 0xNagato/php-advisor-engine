<?php

namespace App\Filament\Pages\Concierge;

use App\Actions\Booking\CreateBooking;
use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Models\Region;
use App\Models\Restaurant;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleWithBooking;
use App\Services\BookingService;
use App\Traits\ManagesBookingForms;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Attributes\Url;
use Stripe\Exception\ApiErrorException;

/**
 * @property Form $form
 */
class ReservationHub extends Page
{
    use ManagesBookingForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $title = 'Reservation Hub';

    protected static ?int $navigationSort = -4;

    protected static ?string $slug = 'concierge/reservation-hub';

    protected ?string $heading = 'Reservation Request';

    protected static string $view = 'filament.widgets.reservation-hub';

    protected $listeners = ['booking-confirmed' => '$refresh'];

    #[Session]
    public ?string $qrCode = null;

    #[Session]
    public ?string $bookingUrl = null;

    #[Session]
    public ?Booking $booking = null;

    public bool $isLoading = false;

    public bool $paymentSuccess = false;

    public string $currency;

    public bool $SMSSent = false;

    public ?array $data = [];

    public Collection $schedulesToday;

    public Collection $schedulesThisWeek;

    protected int|string|array $columnSpan = 'full';

    #[Url]
    public null|string|int $scheduleTemplateId = null;

    #[Url]
    public ?string $date = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('concierge');
    }

    public function mount(): void
    {
        $region = Region::query()->find(session('region', 'miami'));
        $this->timezone = $region->timezone;
        $this->currency = $region->currency;

        if ($this->booking !== null) {
            $this->booking = Booking::with('schedule.restaurant')->find($this->booking->id);
        }

        $this->form->fill();

        $this->schedulesToday = new Collection();
        $this->schedulesThisWeek = new Collection();

        // This is used by the availability calendar to pre-fill the form
        // this should eventually be refactored into its own service.
        if (! $this->booking && $this->scheduleTemplateId && $this->date) {
            $schedule = ScheduleTemplate::query()->find($this->scheduleTemplateId);

            $this->form->fill([
                'date' => $this->date,
                'guest_count' => $schedule->party_size,
                'reservation_time' => $schedule->start_time,
                'restaurant' => $schedule->restaurant_id,
                'select_date' => now($this->timezone)->format('Y-m-d'),
                'radio_date' => now($this->timezone)->format('Y-m-d'),
            ]);

            $this->createBooking($this->scheduleTemplateId, $this->date);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...$this->commonFormComponents(),
                Select::make('restaurant')
                    ->prefixIcon('heroicon-m-building-storefront')
                    ->options(
                        fn () => Restaurant::available()
                            ->where('region', session('region', 'miami'))
                            ->pluck('restaurant_name', 'id')
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

    public function updatedData($data, $key): void
    {
        if ($this->isRedundantRadioDateUpdate($key, $data)) {
            return;
        }

        $this->setDefaultGuestCountIfBlank($key, $data);

        if ($this->isFormFullyFilled()) {
            $userTimezone = $this->timezone;

            /** @var Carbon $requestedDate */
            $requestedDate = Carbon::createFromFormat('Y-m-d', $this->data['date'], $userTimezone);
            $currentDate = Carbon::now($userTimezone);

            if ($this->isSameDayReservation($key, $requestedDate, $currentDate) && $this->isPastReservationTime($userTimezone)) {
                $this->resetSchedules();

                return;
            }

            $reservationTime = $this->adjustReservationTime($userTimezone);
            $restaurantId = $this->form->getState()['restaurant'];
            $endTimeForQuery = $this->calculateEndTimeForQuery($reservationTime, $userTimezone);

            $guestCount = $this->adjustGuestCount($this->form->getState()['guest_count']);

            $this->schedulesToday = $this->getSchedulesToday($restaurantId, $reservationTime, $endTimeForQuery, $guestCount);
            $this->schedulesThisWeek = $this->getSchedulesThisWeek($requestedDate, $currentDate, $restaurantId, $guestCount);
        }
    }

    protected function isRedundantRadioDateUpdate($key, $data): bool
    {
        return $key === 'radio_date' && $data === 'select_date';
    }

    protected function setDefaultGuestCountIfBlank($key, $data): void
    {
        if ($key === 'guest_count' && blank($data)) {
            $this->data['guest_count'] = 2;
        }
    }

    protected function isFormFullyFilled(): bool
    {
        return isset($this->data['restaurant'], $this->data['reservation_time'], $this->data['date'], $this->data['guest_count']);
    }

    protected function isSameDayReservation($key, Carbon $requestedDate, Carbon $currentDate): bool
    {
        return ($key === 'radio_date' || $key === 'select_date') && $currentDate->isSameDay($requestedDate);
    }

    protected function isPastReservationTime($userTimezone): bool
    {
        $reservationTime = Carbon::createFromFormat('H:i:s', $this->form->getState()['reservation_time'], $userTimezone);
        $currentTime = Carbon::now($userTimezone);

        return $reservationTime?->lt($currentTime);
    }

    protected function adjustReservationTime($userTimezone): string
    {
        $reservationTime = Carbon::createFromFormat('H:i:s', $this->form->getState()['reservation_time'], $userTimezone);
        $currentTime = Carbon::now($userTimezone);

        if ($reservationTime?->copy()->subMinutes(self::MINUTES_PAST)->gt($currentTime)) {
            return $reservationTime?->subMinutes(self::MINUTES_PAST)->format('H:i:s');
        }

        return $this->form->getState()['reservation_time'];
    }

    protected function calculateEndTimeForQuery($reservationTime, $userTimezone): string
    {
        $endTime = Carbon::createFromFormat('H:i:s', $reservationTime, $userTimezone)?->addMinutes(self::MINUTES_FUTURE);
        $limitTime = Carbon::createFromTime(23, 59, 0, $userTimezone);

        return $endTime->gt($limitTime) ? '23:59:59' : $endTime->format('H:i:s');
    }

    protected function adjustGuestCount($guestCount): int
    {
        $guestCount = ceil($guestCount);

        return $guestCount % 2 !== 0 ? $guestCount + 1 : $guestCount;
    }

    protected function getSchedulesToday($restaurantId, $reservationTime, $endTimeForQuery, $guestCount): Collection
    {
        return ScheduleWithBooking::with('restaurant')
            ->where('restaurant_id', $restaurantId)
            ->where('booking_date', $this->form->getState()['date'])
            ->where('party_size', $guestCount)
            ->where('start_time', '>=', $reservationTime)
            ->where('start_time', '<=', $endTimeForQuery)
            ->get();
    }

    protected function getSchedulesThisWeek(Carbon $requestedDate, Carbon $currentDate, $restaurantId, $guestCount): Collection
    {
        if ($requestedDate->isSameDay($currentDate)) {
            return ScheduleWithBooking::with('restaurant')
                ->where('restaurant_id', $restaurantId)
                ->where('start_time', $this->form->getState()['reservation_time'])
                ->where('party_size', $guestCount)
                ->whereDate('booking_date', '>', $currentDate)
                ->whereDate('booking_date', '<=', $currentDate->addDays(self::AVAILABILITY_DAYS))
                ->get();
        }

        return new Collection();
    }

    protected function resetSchedules(): void
    {
        $this->schedulesToday = new Collection();
        $this->schedulesThisWeek = new Collection();
    }

    public function createBooking(int $scheduleTemplateId, ?string $date = null): void
    {
        $userTimezone = $this->timezone;
        $data = $this->form->getState();
        $data['date'] = $date ?? $data['date'];

        try {
            $result = CreateBooking::run($scheduleTemplateId, $data, $userTimezone, $this->currency);

            $this->booking = $result->booking;
            $this->bookingUrl = $result->bookingUrl;
            $this->qrCode = $result->qrCode;
        } catch (Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
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
        $this->booking = $this->booking->fresh();

        if ($this->booking->status === BookingStatus::CONFIRMED) {
            return;
        }

        $this->booking->update(['status' => 'cancelled']);
        BookingCancelled::dispatch($this->booking);
        $this->booking = null;
        $this->qrCode = null;
        $this->bookingUrl = null;

        if ($this->scheduleTemplateId && $this->date) {
            $this->redirect(AvailabilityCalendar::getUrl());
        }
    }

    public function resetBooking(): void
    {
        $this->schedulesThisWeek = new Collection();
        $this->schedulesToday = new Collection();

        $this->booking = null;
        $this->qrCode = null;
        $this->bookingUrl = null;
        $this->paymentSuccess = false;
        $this->SMSSent = false;

        $this->form->fill();
    }

    #[On('region-changed')]
    public function regionChanged(): void
    {
        $region = Region::query()->find(session('region', 'miami'));

        $this->timezone = $region->timezone;
        $this->currency = $region->currency;

        $this->resetBooking();
    }

    #[On('sms-sent')]
    public function SMSSent(): void
    {
        $this->SMSSent = true;
    }
}

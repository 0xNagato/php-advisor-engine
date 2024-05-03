<?php

namespace App\Filament\Pages\Concierge;

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Region;
use App\Models\Restaurant;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleWithBooking;
use App\Services\BookingService;
use App\Services\SalesTaxService;
use App\Traits\ManagesBookingForms;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use chillerlan\QRCode\QRCode;
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
use Sentry;
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

    #[Session]
    public ?string $qrCode;

    #[Session]
    public ?string $bookingUrl;

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
        $region = Region::find(session('region'));
        $this->timezone = $region->timezone;
        $this->currency = $region->currency;
        $this->booking = $this->booking?->refresh();

        $this->form->fill();

        $this->schedulesToday = new Collection();
        $this->schedulesThisWeek = new Collection();

        if (! $this->booking && $this->scheduleTemplateId && $this->date) {
            $schedule = ScheduleTemplate::find($this->scheduleTemplateId);

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
                        Restaurant::available()
                            ->where('region', session('region'))
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
        if ($key === 'radio_date' && $data === 'select_date') {
            return;
        }

        if ($key === 'guest_count' && empty($data)) {
            $this->data['guest_count'] = 2;
        }

        if (isset($this->data['restaurant'], $this->data['reservation_time'], $this->data['date'], $this->data['guest_count'])) {
            $userTimezone = $this->timezone;
            $requestedDate = Carbon::createFromFormat('Y-m-d', $this->data['date'], $userTimezone);
            $currentDate = Carbon::now($userTimezone);

            if (($key === 'radio_date' || $key === 'select_date') && $currentDate->isSameDay($requestedDate)) {

                $reservationTime = Carbon::createFromFormat('H:i:s', $this->form->getState()['reservation_time'], $userTimezone);
                $currentTime = Carbon::now($userTimezone);

                // Check if the reservation time is before the current time
                if ($reservationTime->lt($currentTime)) {
                    $this->schedulesToday = new Collection();
                    $this->schedulesThisWeek = new Collection();

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

            $this->schedulesToday = ScheduleWithBooking::where('restaurant_id', $restaurantId)
                ->where('booking_date', $this->form->getState()['date'])
                ->where('party_size', $guestCount)
                ->where('start_time', '>=', $reservationTime)
                ->where('start_time', '<=', $endTimeForQuery)
                ->get();

            if ($requestedDate->isSameDay($currentDate)) {
                $this->schedulesThisWeek = ScheduleWithBooking::where('restaurant_id', $restaurantId)
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

    public function createBooking($scheduleTemplateId, ?string $date = null): void
    {
        $userTimezone = $this->timezone;
        $bookingDate = Carbon::createFromFormat('Y-m-d', $this->data['date'], $userTimezone);
        $currentDate = Carbon::now($userTimezone);

        if ($bookingDate->gt($currentDate->copy()->addMonth())) {
            Notification::make()
                ->title('Booking cannot be created more than one month in advance.')
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        $scheduleTemplate = ScheduleTemplate::find($scheduleTemplateId);

        $data['date'] = $date ?? $data['date'];

        $bookingAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $data['date'].' '.$scheduleTemplate->start_time,
            $this->timezone
        );

        $this->booking = Booking::create([
            'schedule_template_id' => $scheduleTemplateId,
            'guest_count' => $data['guest_count'],
            'concierge_id' => auth()->user()->concierge->id,
            'status' => BookingStatus::PENDING,
            'booking_at' => $bookingAt,
            'currency' => $this->currency,
        ]);

        $taxData = app(SalesTaxService::class)->calculateTax(
            $this->booking->restaurant->region,
            $this->booking->total_fee,
            noTax: config('app.no_tax'),
        );

        $totalWithTaxInCents =
            $this->booking->total_fee + $taxData->amountInCents;

        $this->booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->region,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
        ]);

        try {
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
        } catch (Exception $e) {
            Sentry::captureException($e);

            $this->booking->delete();
            Notification::make()
                ->title('An error occurred while creating the booking.')
                ->danger()
                ->send();

            return;
        }

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
        $region = Region::find(session('region'));

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

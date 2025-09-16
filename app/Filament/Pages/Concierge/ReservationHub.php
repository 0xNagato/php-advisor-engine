<?php

namespace App\Filament\Pages\Concierge;

use App\Actions\Booking\CheckCustomerHasConflictingNonPrimeBooking;
use App\Actions\Booking\CheckCustomerHasNonPrimeBooking;
use App\Actions\Booking\CheckIfConciergeCanOverrideDuplicateChecks;
use App\Actions\Booking\CreateBooking;
use App\Enums\BookingStatus;
use App\Enums\VenueType;
use App\Filament\Pages\IbizaHikeStationBooking;
use App\Models\Booking;
use App\Models\Region;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleWithBookingMV;
use App\Models\Venue;
use App\Services\BookingService;
use App\Traits\FormatsPhoneNumber;
use App\Traits\HandlesVenueClosures;
use App\Traits\ManagesBookingForms;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Attributes\Url;
use Stripe\Exception\ApiErrorException;

/**
 * @property Form $form
 */
class ReservationHub extends Page
{
    use FormatsPhoneNumber;
    use HandlesVenueClosures;
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

    #[Url]
    public ?int $guestCount = null;

    #[Url]
    public ?string $source = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole(['concierge', 'partner']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasActiveRole(['concierge', 'partner']);
    }

    public function mount(): void
    {
        $region = Region::query()->find(session('region', 'miami'));
        $this->timezone = $region->timezone;
        $this->currency = $region->currency;

        if ($this->booking !== null) {
            $this->booking = Booking::query()->find($this->booking->id);

            if ($this->booking->is_refunded_or_partially_refunded) {
                $this->resetBooking();
            }
        }

        $this->form->fill();

        $this->schedulesToday = new Collection;
        $this->schedulesThisWeek = new Collection;

        // This is used by the availability calendar to pre-fill the form
        // this should eventually be refactored into its own service.
        if ($this->scheduleTemplateId && $this->date && ! $this->hasAlreadyProcessedTheseParameters()) {
            $this->markParametersAsProcessed();

            $schedule = ScheduleTemplate::query()->find($this->scheduleTemplateId);

            $this->form->fill([
                'date' => $this->date,
                'guest_count' => $this->guestCount ?? $schedule->party_size,
                'reservation_time' => $schedule->start_time,
                'venue' => $schedule->venue_id,
                'select_date' => now($this->timezone)->format('Y-m-d'),
                'radio_date' => now($this->timezone)->format('Y-m-d'),
            ]);

            $bookingSource = $this->source ?? 'availability_calendar';
            $this->createBooking($this->scheduleTemplateId, $this->date, $bookingSource);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...$this->commonFormComponents(),
                Select::make('venue')
                    ->prefixIcon('heroicon-m-building-storefront')
                    ->options(
                        function () {
                            $query = Venue::query()->active()
                                ->where('region', session('region', 'miami'))
                                ->where('venue_type', '!=', VenueType::HIKE_STATION);

                            // Filter by concierge's allowed venues if applicable
                            if (auth()->user()->hasActiveRole('concierge') && auth()->user()->concierge) {
                                $allowedVenueIds = auth()->user()->concierge->allowed_venue_ids ?? [];

                                // Only apply the filter if there are allowed venues
                                if (filled($allowedVenueIds)) {
                                    // Ensure all IDs are integers
                                    $allowedVenueIds = array_map('intval', $allowedVenueIds);

                                    $query->whereIn('id', $allowedVenueIds);
                                }
                            }

                            return $query->orderBy('name')->pluck('name', 'id');
                        }
                    )
                    ->placeholder('Select Venue')
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

            // Check if the selected date is beyond the maximum allowed days
            $maxDate = $currentDate->copy()->addDays(config('app.max_reservation_days', 30));
            if ($requestedDate->gt($maxDate)) {
                $this->resetSchedules();

                return;
            }

            if ($this->isSameDayReservation($key, $requestedDate,
                $currentDate) && $this->isPastReservationTime($userTimezone)) {
                $this->resetSchedules();

                return;
            }

            $reservationTime = $this->adjustReservationTime($userTimezone);
            $venueId = $this->form->getState()['venue'];
            $endTimeForQuery = $this->calculateEndTimeForQuery($reservationTime, $userTimezone);

            $guestCount = $this->adjustGuestCount($this->form->getState()['guest_count']);

            $this->schedulesToday = $this->getSchedulesToday($venueId, $reservationTime, $endTimeForQuery, $guestCount);
            $this->schedulesThisWeek = $this->getSchedulesThisWeek($requestedDate, $currentDate, $venueId, $guestCount);
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
        return isset($this->data['venue'], $this->data['reservation_time'], $this->data['date'], $this->data['guest_count']);
    }

    protected function isSameDayReservation($key, Carbon $requestedDate, Carbon $currentDate): bool
    {
        return ($key === 'radio_date' || $key === 'select_date') && $currentDate->isSameDay($requestedDate);
    }

    protected function isPastReservationTime($userTimezone): bool
    {
        $reservationTime = Carbon::createFromFormat('H:i:s', $this->form->getState()['reservation_time'],
            $userTimezone);
        $currentTime = Carbon::now($userTimezone);

        return $reservationTime?->lt($currentTime);
    }

    protected function adjustReservationTime($userTimezone): string
    {
        $reservationTime = Carbon::createFromFormat('H:i:s', $this->form->getState()['reservation_time'],
            $userTimezone);
        $currentTime = Carbon::now($userTimezone);

        if ($reservationTime?->copy()->subMinutes(self::MINUTES_PAST)->gt($currentTime)) {
            return $reservationTime?->subMinutes(self::MINUTES_PAST)->format('H:i:s');
        }

        return $this->form->getState()['reservation_time'];
    }

    protected function calculateEndTimeForQuery($reservationTime, $userTimezone): string
    {
        $endTime = Carbon::createFromFormat('H:i:s', $reservationTime,
            $userTimezone)?->addMinutes(self::MINUTES_FUTURE);
        $limitTime = Carbon::createFromTime(23, 59, 0, $userTimezone);

        return $endTime->gt($limitTime) ? '23:59:59' : $endTime->format('H:i:s');
    }

    protected function adjustGuestCount($guestCount): int
    {
        $guestCount = ceil($guestCount);

        return $guestCount % 2 !== 0 ? $guestCount + 1 : $guestCount;
    }

    protected function getSchedulesToday($venueId, $reservationTime, $endTimeForQuery, $guestCount): Collection
    {
        $schedules = ScheduleWithBookingMV::with('venue')
            ->where('venue_id', $venueId)
            ->where('booking_date', $this->form->getState()['date'])
            ->where('party_size', $guestCount)
            ->where('start_time', '>=', $reservationTime)
            ->where('start_time', '<=', $endTimeForQuery)
            ->get();

        $venue = Venue::query()->find($venueId);

        // Apply closure rules if needed
        if ($this->isClosedDate($this->form->getState()['date'])) {
            $schedules = $this->applySingleVenueClosureRules($schedules, $this->form->getState()['date'], $venue->slug);
        }

        // Apply cutoff time logic
        $currentTime = Carbon::now($this->timezone)->format('H:i:s');

        if ($venue->cutoff_time && $currentTime > $venue->cutoff_time) {
            $schedules->each(function ($schedule) {
                $schedule->is_available = true;
                $schedule->remaining_tables = 0;
                $schedule->is_bookable = false;
            });
        }

        return $schedules;
    }

    protected function getSchedulesThisWeek(
        Carbon $requestedDate,
        Carbon $currentDate,
        $venueId,
        $guestCount
    ): Collection {
        if ($requestedDate->isSameDay($currentDate)) {
            return ScheduleWithBookingMV::with('venue')
                ->where('venue_id', $venueId)
                ->where('start_time', $this->form->getState()['reservation_time'])
                ->where('party_size', $guestCount)
                ->whereDate('booking_date', '>', $currentDate)
                ->whereDate('booking_date', '<=', $currentDate->addDays(config('app.max_reservation_days', 30)))
                ->get();
        }

        return new Collection;
    }

    protected function resetSchedules(): void
    {
        $this->schedulesToday = new Collection;
        $this->schedulesThisWeek = new Collection;
    }

    public function createBooking(
        int $scheduleTemplateId,
        ?string $date = null,
        ?string $source = 'reservation_hub'
    ): void {
        $data = $this->form->getState();
        $data['date'] = $date ?? $data['date'];

        try {
            $device = isPrimaApp() ? 'mobile_app' : 'web';
            $result = CreateBooking::run(
                scheduleTemplateId: $scheduleTemplateId,
                data: $data,
                source: $source,
                device: $device
            );

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
        if (! config('app.bookings_enabled')) {
            $this->dispatch('open-modal', id: 'bookings-disabled-modal');
            $this->isLoading = false;

            return;
        }

        // Validate the phone number
        $phone = $form['phone'] ?? '';
        $formattedPhone = $this->getInternationalFormattedPhoneNumber($phone);

        if (blank($formattedPhone)) {
            Notification::make()
                ->title('Invalid Phone Number')
                ->body('Please enter a valid phone number.')
                ->danger()
                ->send();
            $this->isLoading = false;

            return;
        }

        // Validate the email address
        $email = $form['email'] ?? '';

        if ($email !== 'prima@primavip.co') {
            $validator = Validator::make($form, [
                'email' => ['required', 'email:rfc,dns'],
            ]);

            if ($validator->fails()) {
                Notification::make()
                    ->title('Invalid Email Address')
                    ->body('Please enter a valid email address.')
                    ->danger()
                    ->send();
                $this->isLoading = false;

                return;
            }
        }

        // Update the form with the formatted phone number
        $form['phone'] = $formattedPhone;

        if (! $this->booking->prime_time) {
            // Check for real customer confirmation
            if (! ($form['real_customer_confirmation'] ?? false)) {
                Notification::make()
                    ->title('Confirmation Required')
                    ->body('Please confirm that you are booking for a real customer.')
                    ->danger()
                    ->send();
                $this->isLoading = false;

                return;
            }

            // Check if the concierge can override duplicate checks
            $canOverride = CheckIfConciergeCanOverrideDuplicateChecks::run($this->booking, $formattedPhone);

            if (! $canOverride) {
                // Check for existing non-prime booking
                $hasExistingBooking = CheckCustomerHasNonPrimeBooking::run(
                    $form['phone'],
                    $this->booking->booking_at->format('Y-m-d'),
                    $this->booking->venue->timezone
                );

                if ($hasExistingBooking) {
                    Notification::make()
                        ->title('Booking Not Allowed')
                        ->body('Customer already has a non-prime booking for this day.')
                        ->danger()
                        ->send();
                    $this->isLoading = false;

                    return;
                }

                // Check for conflicting non-prime booking within a 2-hour window
                $hasConflictingBooking = CheckCustomerHasConflictingNonPrimeBooking::run(
                    $form['phone'],
                    $this->booking->booking_at
                );

                if ($hasConflictingBooking) {
                    $conflictDate = $hasConflictingBooking->booking_at->format('F j');
                    $conflictTime = $hasConflictingBooking->booking_at->format('g:i A');

                    Notification::make()
                        ->title('Conflicting Booking Time')
                        ->body("Customer already has a no‑fee booking on {$conflictDate} at {$conflictTime}. Bookings must be at least 2 hours apart. Please select a different time.")
                        ->danger()
                        ->send();
                    $this->isLoading = false;

                    return;
                }
            }
        }

        try {
            app(BookingService::class)->processBooking($this->booking, $form);

            $this->booking->update(['concierge_referral_type' => 'concierge']);

            $this->isLoading = false;
            $this->paymentSuccess = true;
        } catch (ApiErrorException $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
            $this->isLoading = false;
        }
    }

    public function cancelBooking(): void
    {
        $this->booking = $this->booking->fresh();

        if (! in_array($this->booking->status, [BookingStatus::PENDING, BookingStatus::GUEST_ON_PAGE])) {
            return;
        }

        // Log the activity before updating the status
        activity()
            ->performedOn($this->booking)
            ->withProperties([
                'booking_id' => $this->booking->id,
                'new_status' => BookingStatus::ABANDONED->value,
                'concierge_id' => auth()->user()->concierge->id,
                'concierge_name' => auth()->user()->name,
                'previous_status' => $this->booking->status->value,
            ])
            ->log('ReservationHub - Booking marked as abandoned');

        // Store booking source and calendar params before nullifying booking
        $bookingSource = $this->booking?->source;
        $scheduleTemplateId = $this->scheduleTemplateId; // Store before potential reset
        $date = $this->date; // Store before potential reset

        $this->booking->update(['status' => BookingStatus::ABANDONED]);
        $this->booking = null;
        $this->qrCode = null;
        $this->bookingUrl = null;

        if (isPrimaApp()) {
            $this->js("
                if (window.ReactNativeWebView) {
                    window.ReactNativeWebView.postMessage(JSON.stringify({
                        type: 'abandonReservation'
                    }));
                }
            ");
        }

        // --- Corrected Redirect Logic ---
        // 1. Prioritize redirect back to Hike Station if that was the source
        if ($bookingSource === 'hike_station_booking_form') {
            $this->redirect(IbizaHikeStationBooking::getUrl());
        } // 2. Otherwise, check if came from Availability Calendar (using stored values)
        elseif ($scheduleTemplateId && $date) {
            $this->redirect(AvailabilityCalendar::getUrl());
        }
        // 3. If neither, stay on ReservationHub (no redirect)
    }

    public function resetBooking(): void
    {
        $this->schedulesThisWeek = new Collection;
        $this->schedulesToday = new Collection;

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

    public function resetBookingAndReturnToAvailabilityCalendar(): void
    {
        $this->resetBooking();
        $this->redirect(AvailabilityCalendar::getUrl());
    }

    protected function hasAlreadyProcessedTheseParameters(): bool
    {
        if (! $this->scheduleTemplateId || ! $this->date) {
            return false;
        }

        $key = "processed_booking_{$this->scheduleTemplateId}_{$this->date}_{$this->guestCount}";

        return session()->has($key);
    }

    protected function markParametersAsProcessed(): void
    {
        $key = "processed_booking_{$this->scheduleTemplateId}_{$this->date}_{$this->guestCount}";
        session()->put($key, true);
    }

    public function isDateBeyondLimit(): bool
    {
        if (! isset($this->data['date'])) {
            return false;
        }

        $selectedDate = Carbon::createFromFormat('Y-m-d', $this->data['date'], $this->timezone);
        $maxDate = Carbon::now($this->timezone)->addDays(config('app.max_reservation_days', 30));

        return $selectedDate->gt($maxDate);
    }

    public function getMaxReservationDays(): int
    {
        return config('app.max_reservation_days', 30);
    }
}

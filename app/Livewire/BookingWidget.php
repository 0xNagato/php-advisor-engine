<?php

namespace App\Livewire;

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
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Stripe\Exception\ApiErrorException;

class BookingWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.booking-widget';

    /**
     * @var Collection<Restaurant>|null
     */
    public ?Collection $restaurants;

    public ?Restaurant $selectedRestaurant;

    public int|string|null $selectedRestaurantId;

    /**
     * @var Collection<Schedule>|null
     */
    public ?Collection $schedules;

    /**
     * @var Collection<Schedule>|null
     */
    public ?Collection $unavailableSchedules;

    public ?Schedule $selectedSchedule;

    public int|string|null $selectedScheduleId;

    public ?int $guestCount;

    #[Session]
    public ?string $qrCode;

    #[Session]
    public ?string $bookingUrl;

    #[Session]
    public ?Booking $booking;

    public string $selectedDate;

    public bool $isLoading = false;

    public bool $paymentSuccess = false;

    public bool $SMSSent = false;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->restaurants = Restaurant::openToday()->get();
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedRestaurantId')
                    ->options($this->restaurants->pluck('restaurant_name', 'id'))
                    ->placeholder('Select a restaurant')
                    ->live()
                    ->hiddenLabel()
                    ->searchable()
                    ->selectablePlaceholder(false),
            ])
            ->columns(1);
    }

    public function updatedSelectedDate($value): void
    {
        $this->selectedRestaurantId = null;
        $this->selectedRestaurant = null;
        $this->selectedScheduleId = null;
        $this->selectedSchedule = null;
        $this->unavailableSchedules = null;

        $this->restaurants = Restaurant::openOnDate($value)->get();
    }

    public function updatedSelectedRestaurantId($value): void
    {
        $this->selectedRestaurant = Restaurant::openOnDate($this->selectedDate)->find($value);

        $userTimezone = auth()->user()->timezone; // assuming the timezone column is named 'timezone'
        $currentTime = now()->setTimezone($userTimezone)->format('H:i:s');

        $selectedDateInUserTimezone = Carbon::createFromFormat('Y-m-d', $this->selectedDate)->setTimezone($userTimezone)->format('Y-m-d');

        if ($selectedDateInUserTimezone === now()->setTimezone($userTimezone)->format('Y-m-d')) {
            $this->schedules = $this->selectedRestaurant->availableSchedules->where('start_time', '>=', $currentTime);
            $this->unavailableSchedules = $this->selectedRestaurant->unavailableSchedules->where('start_time', '>=', $currentTime);
        } else {
            $this->schedules = Restaurant::find($this->selectedRestaurantId)->availableSchedules;
            $this->unavailableSchedules = Restaurant::find($this->selectedRestaurantId)->unavailableSchedules;
        }

        $this->selectedScheduleId = null;
    }

    public function updatedSelectedScheduleId($value): void
    {
        $this->selectedSchedule = Schedule::find($value);
    }

    public function updatedGuestCount(): void
    {
    }

    /**
     * @throws Exception
     */
    public function createBooking(): void
    {
        $this->booking = Booking::create([
            'schedule_id' => $this->selectedScheduleId,
            'guest_count' => $this->guestCount,
            'concierge_id' => auth()->user()->concierge->id,
            'status' => BookingStatus::PENDING,
            'booking_at' => $this->selectedDate.' '.$this->selectedSchedule->start_time,
        ]);

        $taxData = app(SalesTaxService::class)->calculateTax('miami', $this->booking->total_fee);
        $totalWithTaxInCents = $this->booking->total_fee + $taxData->amountInCents;

        $this->booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->city,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
        ]);

        $shortUrlQr = ShortURL::destinationUrl(route('bookings.create', ['token' => $this->booking->uuid, 'r' => 'qr']))
            ->make();

        $shortUrl = ShortURL::destinationUrl(route('bookings.create', ['token' => $this->booking->uuid, 'r' => 'sms']))
            ->make();

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

        $this->selectedRestaurantId = null;
        $this->selectedRestaurant = null;
        $this->selectedScheduleId = null;
        $this->selectedSchedule = null;
        $this->guestCount = null;
        $this->unavailableSchedules = null;
    }

    public function resetBooking(): void
    {
        $this->booking = null;
        $this->qrCode = null;
        $this->bookingUrl = null;
        $this->paymentSuccess = false;
        $this->SMSSent = false;

        $this->selectedRestaurantId = null;
        $this->selectedRestaurant = null;
        $this->selectedScheduleId = null;
        $this->selectedSchedule = null;
        $this->guestCount = null;
        $this->unavailableSchedules = null;
    }

    #[On('sms-sent')]
    public function SMSSent(): void
    {
        $this->SMSSent = true;
    }
}

<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Restaurant;
use App\Models\Schedule;
use App\Services\BookingService;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use chillerlan\QRCode\QRCode;
use DateTime;
use Exception;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Stripe\Exception\ApiErrorException;

class BookingWidget extends Widget
{
    protected static string $view = 'filament.widgets.booking-widget';

    /**
     * @var Collection<Restaurant>|null
     */
    public ?Collection $restaurants;

    public ?Restaurant $selectedRestaurant;

    public int|string|null $selectedRestaurantId;

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

    public function updatedSelectedRestaurantId($value): void
    {
        $this->selectedRestaurant = Restaurant::openToday()->find($value);
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
            'booking_at' => $this->selectedSchedule->start_time->setDateFrom(new DateTime($this->selectedDate)),
        ]);

        $shortUrlQr = ShortURL::destinationUrl(route('bookings.create', ['token' => $this->booking->uuid, 'r' => 'qr']))
            ->make();

        $shortUrl = ShortURL::destinationUrl(route('bookings.create', ['token' => $this->booking->uuid, 'r' => 'sms']))
            ->make();

        $this->bookingUrl = $shortUrl->default_short_url;

        $this->qrCode = (new QRCode())->render($shortUrlQr->default_short_url);
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
        $this->booking = null;
        $this->qrCode = null;
        $this->bookingUrl = null;
    }

    #[On('sms-sent')]
    public function SMSSent($messageId): void
    {
        $this->SMSSent = true;
    }
}

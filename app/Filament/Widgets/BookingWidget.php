<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Restaurant;
use App\Models\Schedule;
use chillerlan\QRCode\QRCode;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Session;

class BookingWidget extends Widget
{
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

    protected static string $view = 'filament.widgets.booking-widget';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->restaurants = Restaurant::openToday()->get();
    }

    public function updatedSelectedRestaurantId($value): void
    {
        $this->selectedRestaurant = $this->restaurants->find($value);
    }

    public function updatedSelectedScheduleId($value): void
    {
        $this->selectedSchedule = Schedule::find($value);
    }

    public function updatedGuestCount(): void
    {
    }

    public function createBooking(): void
    {
        $this->booking = Booking::create([
            'schedule_id' => $this->selectedScheduleId,
            'guest_count' => $this->guestCount,
            'concierge_id' => auth()->user()->concierge->id,
            'status' => 'pending',
            'booking_at' => $this->selectedSchedule->start_time,
        ]);

        // ds($this->booking);

        $this->bookingUrl = route('bookings.create', ['token' => $this->booking->uuid]);

        $this->qrCode = (new QRCode())->render($this->bookingUrl);
    }

    public function cancelBooking(): void
    {
        $this->booking->update(['status' => 'cancelled']);
        $this->booking = null;
        $this->qrCode = null;
        $this->bookingUrl = null;
    }
}

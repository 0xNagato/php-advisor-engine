<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Restaurant;
use App\Models\Schedule;
use chillerlan\QRCode\QRCode;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

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

    public ?string $qrCode;

    public ?string $bookingUrl;

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
        $booking = Booking::create([
            'schedule_id' => $this->selectedScheduleId,
            'guest_count' => $this->guestCount,
            'concierge_id' => auth()->user()->concierge->id,
            'status' => 'pending',
            'booking_at' => $this->selectedSchedule->start_time,
        ]);

        $this->bookingUrl = route('bookings.create', ['token' => $booking->uuid]);

        $this->qrCode = (new QRCode())->render($this->bookingUrl);
    }
}

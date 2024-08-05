<?php

namespace App\Livewire\Venue;

use App\Models\Booking;
use DateTime;
use DateTimeZone;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

class VenueBookingConfirmation extends Page
{
    public Booking $booking;

    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.venue-booking-confirmation';

    public function mount(string $token): void
    {
        $this->booking = Booking::with('venue')->where('uuid', $token)->firstOrFail();
    }

    public function confirmBookingAction(): Action
    {
        return Action::make('confirmBooking')
            ->label('Confirm Booking')
            ->color(Color::Green)
            ->requiresConfirmation()
            ->action(fn () => $this->confirmBooking());
    }

    private function confirmBooking(): void
    {
        if ($this->booking->venue_confirmed_at === null) {
            Log::info('Venue confirmed booking', [
                'name' => $this->booking->venue->name,
                'booking' => $this->booking->id,
            ]);
            $this->booking->update(['venue_confirmed_at' => now()]);
        }

        Notification::make()
            ->title('Thank you for confirming the booking')
            ->success()
            ->send();
    }

    /**
     * @throws Exception
     */
    /**
     * Determines if the current time is past the allowable booking confirmation time,
     * which is one hour after the booking time. This method uses the session timezone
     * to ensure consistent time comparisons.
     *
     * Problems Encountered:
     * 1. **Carbon Timezone Handling**:
     *    - Initial attempts using Carbon resulted in incorrect time comparisons.
     *    — The issue was likely due to implicit timezone handling in Carbon, where the
     *      `booking_at` time was not always correctly interpreted in the intended timezone.
     *
     * 2. **Explicit Timezone Conversion**:
     *    - Switching to PHP's native `DateTime` and `DateTimeZone` classes provided more
     *      explicit control over timezone conversions, ensuring that both the booking time,
     *      and the current time were correctly interpreted and compared.
     *
     * @return bool True if the current time is past the booking time plus one hour, false otherwise.
     *
     * @throws Exception
     */
    #[Computed]
    public function isPastBookingTime(): bool
    {
        $timezone = session('timezone', 'UTC');
        $bookingTime = new DateTime($this->booking->booking_at, new DateTimeZone($timezone));
        $bookingTimePlusOneHour = (clone $bookingTime)->modify('+1 hour');
        $currentTime = new DateTime('now', new DateTimeZone($timezone));

        return $currentTime > $bookingTimePlusOneHour;
    }
}

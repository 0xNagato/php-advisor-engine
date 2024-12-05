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

    public bool $showUndoButton = false;

    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.venue-booking-confirmation';

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
        $this->showUndoButton = $this->booking->venue_confirmed_at !== null &&
            $this->booking->venue_confirmed_at->isAfter(now()->subHour());
    }

    public function confirmBooking(): void
    {
        if ($this->booking->venue_confirmed_at === null) {
            Log::info('Venue confirmed booking', [
                'name' => $this->booking->venue->name,
                'booking' => $this->booking->id,
            ]);
            $this->booking->update(['venue_confirmed_at' => now()]);
            $this->showUndoButton = true;
        }

        Notification::make()
            ->title('Thank you for confirming the booking')
            ->success()
            ->send();
    }

    public function undoConfirmationAction(): Action
    {
        return Action::make('undoConfirmation')
            ->label('Undo Confirmation')
            ->color(Color::Gray)
            ->requiresConfirmation()
            ->modalHeading('Undo Booking Confirmation')
            ->modalDescription('Are you sure you want to undo this booking confirmation? The guest will be notified of this change.')
            ->modalSubmitActionLabel('Yes, undo confirmation')
            ->modalCancelActionLabel('No, keep confirmation')
            ->action(fn () => $this->undoConfirmation());
    }

    private function undoConfirmation(): void
    {
        $this->booking->update(['venue_confirmed_at' => null]);
        $this->showUndoButton = false;

        Notification::make()
            ->title('Booking confirmation has been undone')
            ->success()
            ->send();
    }

    /**
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

    #[Computed]
    public function bookingDetails(): array
    {
        if ($this->booking->is_prime) {
            $totalFee = $this->booking->total_fee;
            $venueEarnings = $totalFee * 0.60; // 60% as per the agreement

            return [
                'type' => 'prime',
                'totalFee' => $totalFee,
                'venueEarnings' => $venueEarnings,
            ];
        }

        $perDinerFee = $this->booking->venue->non_prime_fee_per_head;
        $totalFee = $perDinerFee * $this->booking->guest_count;

        return [
            'type' => 'non-prime',
            'perDinerFee' => $perDinerFee,
            'totalFee' => $totalFee,
        ];
    }

    public function confirmBookingAction(): Action
    {
        return Action::make('confirmBooking')
            ->label('Are You Sure You Want to Confirm?')
            ->color('success')
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->action(function () {
                if ($this->booking->venue_confirmed_at === null) {
                    Log::info('Venue confirmed booking', [
                        'name' => $this->booking->venue->name,
                        'booking' => $this->booking->id,
                    ]);
                    $this->booking->update(['venue_confirmed_at' => now()]);
                    $this->showUndoButton = true;
                }

                Notification::make()
                    ->title('Thank you for confirming the booking')
                    ->success()
                    ->send();
            });
    }
}

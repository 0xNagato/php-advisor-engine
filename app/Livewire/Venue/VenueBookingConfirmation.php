<?php

namespace App\Livewire\Venue;

use App\Constants\BookingPercentages;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ScheduleTemplate;
use App\Models\VenueTimeSlot;
use App\Notifications\Booking\CustomerBookingConfirmed;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

class VenueBookingConfirmation extends Page
{
    public const int MINUTES_BEFORE_BOOKING_CUTOFF = 5;

    public Booking $booking;

    public bool $showUndoButton = false;

    public ?string $cutoffTime = null;

    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.venue-booking-confirmation';

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
        $this->showUndoButton = $this->booking->venue_confirmed_at !== null &&
            $this->booking->venue_confirmed_at->isAfter(now()->subHour());

        // Calculate cutoff time
        $bookingTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->booking->booking_at,
            $this->booking->venue->timezone
        );

        $this->cutoffTime = $bookingTime
            ->copy()
            ->subMinutes(self::MINUTES_BEFORE_BOOKING_CUTOFF)
            ->format('g:i A');
    }

    public function confirmBooking(): void
    {
        if ($this->isBookingCancelled()) {
            return;
        }

        if ($this->booking->venue_confirmed_at === null) {
            $this->booking->load('venue.inRegion');
            $payload = [
                'venue_confirmed_at' => now(),
            ];

            activity()
                ->performedOn($this->booking)
                ->withProperties($payload)
                ->log('Venue '.$this->booking->venue->name.' confirmed booking');

            Log::info('Venue confirmed booking', [
                'name' => $this->booking->venue->name,
                'booking' => $this->booking->id,
            ]);

            $this->booking->update($payload);
            $this->showUndoButton = true;

            if ($this->booking->is_non_prime_ibiza_big_group) {
                $this->booking->notify(new CustomerBookingConfirmed);
            }
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
        $venueTimezone = $this->booking->venue->timezone;

        $bookingTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->booking->booking_at,
            $venueTimezone
        );

        $currentTime = now($venueTimezone);

        // Check if we're within the cutoff period before the booking
        return $currentTime->diffInMinutes($bookingTime, false) <= self::MINUTES_BEFORE_BOOKING_CUTOFF;
    }

    #[Computed]
    public function isBookingCancelled(): bool
    {
        return $this->booking->status === BookingStatus::CANCELLED;
    }

    #[Computed]
    public function bookingDetails(): array
    {
        if ($this->booking->is_prime) {
            $totalFee = $this->booking->total_fee;
            $venueEarnings = $totalFee * ($this->booking->venue->payout_venue / 100);

            return [
                'type' => 'prime',
                'totalFee' => $totalFee,
                'venueEarnings' => $venueEarnings,
            ];
        }

        $perDinerFee = $this->booking->venue->non_prime_fee_per_head;

        if ($this->booking->schedule_template_id) {
            $scheduleTemplate = ScheduleTemplate::query()->find($this->booking->schedule_template_id);

            // Override price per head from the schedule template if available
            if ($scheduleTemplate?->price_per_head) {
                $perDinerFee = $scheduleTemplate->price_per_head;
            }

            // Check for an override in a specific venue time slot
            $override = VenueTimeSlot::query()
                ->where('schedule_template_id', $this->booking->schedule_template_id)
                ->whereDate('booking_date', $this->booking->booking_at)
                ->value('price_per_head');

            // Override price per diner fee if a specific price is set
            if (! is_null($override)) {
                $perDinerFee = $override;
            }
        }

        $subtotal = $perDinerFee * $this->booking->guest_count;
        $venueFee = $subtotal * (BookingPercentages::NON_PRIME_PROCESSING_FEE_PERCENTAGE / 100);
        $totalFee = $subtotal + $venueFee;

        $guestCount = $this->booking->guest_count;

        return [
            'type' => 'non-prime',
            'guestCount' => $guestCount,
            'perDinerFee' => $perDinerFee,
            'venueFee' => $venueFee,
            'totalFee' => $totalFee,
        ];
    }

    public function confirmBookingAction(): Action
    {
        return Action::make('confirmBooking')
            ->label('Confirm Booking Now')
            ->color('success')
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->disabled($this->isBookingCancelled())
            ->action(fn () => $this->confirmBooking());
    }
}

<x-layouts.simple-wrapper>
    <div class="flex flex-col w-full gap-4 p-4 mx-4 bg-white rounded-lg shadow-sm ring-1 ring-gray-950/5">
        <h1 class="text-2xl tracking-tight text-center text-gray-950 dm-serif">
            @if ($booking->venue_confirmed_at)
                Thank You for Confirming the Reservation!
            @else
                Confirm Booking Request
            @endif
        </h1>

        @if (filled($booking->notes))
            <div class="p-4 border rounded-md shadow-sm bg-gray-50">
                <h2 class="mb-2 font-semibold text-gray-800">Reservation Notes</h2>
                <p class="text-sm text-gray-700">{{ $booking->notes }}</p>
            </div>
        @endif

        @if ($booking->venue_confirmed_at)
            <div class="space-y-4 text-center">
                <p>We've notified the guests and reminded them to be on time for their reservation.</p>
                @if ($this->showUndoButton)
                    {{ $this->undoConfirmationAction }}
                @endif
            </div>
        @else
            @if ($this->isPastBookingTime)
                <div class="space-y-4 text-center">
                    <p class="text-red-500">
                        This reservation can no longer be confirmed as it is past the allowable confirmation time.
                    </p>
                </div>
            @else
                {{ $this->confirmBookingAction }}
            @endif
        @endif

        <div class="pt-4 mt-4 border-t">
            <div class="p-4 text-sm border rounded-lg bg-gray-50">
                @if ($this->bookingDetails['type'] === 'prime')
                    <h3 class="mb-3 font-semibold">Prime Booking Details</h3>
                    <div class="space-y-2">
                        <p>Total Booking Fee: {{ money($this->bookingDetails['totalFee'], $booking->currency) }}
                        </p>
                        <p>Venue Earnings:
                            {{ money($this->bookingDetails['venueEarnings'], $booking->currency) }}</p>
                    </div>
                @else
                    <h3 class="mb-3 font-semibold">Non-Prime Booking Details</h3>
                    <div class="space-y-2">
                        <p>Incentive Plan: {{ money($this->bookingDetails['perDinerFee'] * 100, $booking->currency) }}
                            per diner</p>
                        <p>Total Fee: {{ money($this->bookingDetails['totalFee'] * 100, $booking->currency) }}</p>
                    </div>
                @endif
            </div>
            <p class="mt-4 text-xs font-medium text-center">Thank you for using PRIMA to fill your dining room!
            </p>
        </div>

    </div>
    <div class="w-full mt-4">
        <livewire:booking.invoice-small :booking="$booking" />
    </div>
</x-layouts.simple-wrapper>

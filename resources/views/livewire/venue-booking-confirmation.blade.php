<x-layouts.simple-wrapper>
    <div class="mx-4 flex w-full flex-col gap-4 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5">
        <h1 class="text-center text-2xl tracking-tight text-gray-950 dm-serif">
            @if($booking->venue_confirmed_at)
                Thank You for Confirming the Reservation!
            @else
                Confirm Booking Request
            @endif
        </h1>

        @if(filled($booking->notes))
            <div class="rounded-md border bg-gray-50 p-4 shadow-sm">
                <h2 class="mb-2 font-semibold text-gray-800">Reservation Notes</h2>
                <p class="text-sm text-gray-700">{{ $booking->notes }}</p>
            </div>
        @endif

        @if($booking->venue_confirmed_at)
            <div class="text-center space-y-4">
                <p>We've notified the guests and reminded them to be on time for their reservation.</p>
                <p class="font-semibold">Thank you for using PRIMA!</p>
            </div>
        @else
            @if($this->isPastBookingTime)
                <div class="text-center space-y-4">
                    <p class="text-red-500">
                        This reservation can no longer be confirmed as it is past the allowable confirmation time.
                    </p>
                    <p class="font-semibold">Thank you for using PRIMA!</p>
                </div>
            @else
                {{ $this->confirmBookingAction }}
            @endif
        @endif
    </div>

    <div class="mt-4 w-full">
        <livewire:booking.invoice-small :booking="$booking"/>
    </div>
</x-layouts.simple-wrapper>

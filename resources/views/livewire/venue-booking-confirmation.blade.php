<x-layouts.simple-wrapper>

    <div class="flex flex-col gap-2 p-4 mx-4 w-full bg-white rounded-lg ring-1 shadow-sm ring-gray-950/5">
        <h1 class="text-2xl tracking-tight text-center text-gray-950 dm-serif">
            @if ($this->isBookingCancelled())
                This Reservation Has Been Cancelled
            @elseif ($booking->venue_confirmed_at)
                Thank You for Confirming the Reservation!
            @else
                Please Confirm Booking:
            @endif
        </h1>
        {{-- <div class="mb-2 w-full">
            <livewire:booking.invoice-small :booking="$booking" :showAmount="false" />
        </div> --}}
        <div class="p-4 space-y-1 text-sm bg-gray-50 rounded-lg border">
            <p><span class="font-semibold">Venue:</span> {{ $booking->venue->name }}</p>
            <p><span class="font-semibold">Booking:</span> {{ $booking->booking_at->format('M j, Y') }} @
                {{ $booking->booking_at->format('g:i A') }}</p>
            <p><span class="font-semibold">Customer:</span> {{ $booking->guest_first_name }}
                {{ $booking->guest_last_name }}</p>
            <p><span class="font-semibold">Phone:</span> {{ $booking->guest_phone }}</p>
            <p><span class="font-semibold">Email:</span> {{ $booking->guest_email }}</p>
            <p><span class="font-semibold">Guest Count:</span> {{ $booking->guest_count }}</p>
        </div>
        @if (filled($booking->notes))
            <div class="p-4 bg-red-50 rounded-md border border-red-500 shadow-sm">
                <h2 class="mb-2 font-semibold text-red-800">Reservation Notes:</h2>
                <p class="text-sm text-red-700">{{ $booking->notes }}</p>
            </div>
        @endif

        @if ($this->isBookingCancelled())
            <div
                class="flex flex-col justify-center items-center p-3 space-y-1 text-sm bg-red-50 rounded-lg border border-red-200">
                <p class="font-medium text-red-600">
                    This reservation has been cancelled and cannot be confirmed.
                </p>
                <p class="text-red-500">
                    No action is required from you at this time.
                </p>
            </div>
        @elseif ($booking->venue_confirmed_at)
            <div
                class="flex flex-col justify-center items-center p-3 space-y-1 text-sm text-gray-600 bg-gray-50 rounded-lg border border-gray-200">
                <p>We've notified the guests and reminded them to be on time for their reservation.</p>
                @if ($this->showUndoButton)
                    {{ $this->undoConfirmationAction }}
                @endif
            </div>
        @else
            @if ($this->isPastBookingTime)
                <div
                    class="flex flex-col justify-center items-center p-3 space-y-1 text-sm bg-red-50 rounded-lg border border-red-200">
                    <p class="text-red-600">
                        This reservation cannot be confirmed
                    </p>
                    <p class="text-red-500">
                        Confirmation time has passed. Bookings must be confirmed at least
                        {{ self::MINUTES_BEFORE_BOOKING_CUTOFF }} minutes before the reservation time.
                    </p>
                </div>
            @else
                <div
                    class="flex flex-col justify-center items-center p-3 space-y-1 text-sm bg-gray-50 rounded-lg border border-gray-200">
                    <p class="mb-2 text-center text-gray-600">
                        Please confirm this booking before<br>{{ $cutoffTime }}
                        <span class="text-gray-500">
                            ({{ self::MINUTES_BEFORE_BOOKING_CUTOFF }} minutes before the reservation time)
                        </span>
                    </p>
                    {{ $this->confirmBookingAction }}
                </div>
            @endif
        @endif

        <div
            class="flex flex-col justify-center items-center p-3 space-y-1 text-sm text-gray-600 bg-blue-50 rounded-lg border border-blue-200">
            @if ($booking->concierge)
                <span class="text-xs font-medium text-blue-600">This booking request is created by:</span>
                <span class="font-medium">
                    {{ $booking->concierge->user->name }}{{ $booking->concierge->hotel_name ? ' / ' . $booking->concierge->hotel_name : '' }}
                </span>

                @if ($booking->vipCode)
                    <span class="text-xs font-medium text-blue-600">VIP Code:</span>
                    <span class="font-medium">{{ $booking->vipCode->code }}</span>
                @endif
            @endif
        </div>

        @if (!$this->isBookingCancelled())
            <div class="pt-2">
                <div class="p-4 text-sm bg-gray-50 rounded-lg border">
                    @if ($this->bookingDetails['type'] === 'prime')
                        <h3 class="mb-3 font-semibold">Earnings Details:</h3>
                        <div class="space-y-2">
                            <p><span class="font-semibold">Total Booking Fee:</span>
                                {{ money($this->bookingDetails['totalFee'], $booking->currency) }}
                            </p>
                            <p class="p-2 font-bold text-green-700 bg-green-50 rounded-md border border-green-200">
                                <span class="font-semibold">You Earn:</span>
                                {{ money($this->bookingDetails['venueEarnings'], $booking->currency) }}
                            </p>
                        </div>
                    @else
                        <h3 class="mb-3 font-semibold">Non-Prime Booking Details:</h3>
                        <div class="space-y-2">
                            <p><span class="font-semibold">Incentive Plan:</span>
                                {{ money($this->bookingDetails['perDinerFee'] * 100, $booking->currency) }} per guest
                            </p>
                            <p><span class="font-semibold">Guest Count:</span>
                                {{ $this->bookingDetails['guestCount'] }}
                            </p>
                            <p><span class="font-semibold">PRIMA Platform Fee:</span>
                                {{ money($this->bookingDetails['venueFee'] * 100, $booking->currency) }}
                            </p>
                            <p><span class="font-semibold">Total Fee:</span>
                                {{ money($this->bookingDetails['totalFee'] * 100, $booking->currency) }}</p>
                        </div>
                    @endif
                </div>
                <p class="mt-4 text-xs font-medium text-center">Thank you for using PRIMA to fill your dining room!
                </p>
            </div>
        @endif

    </div>
</x-layouts.simple-wrapper>

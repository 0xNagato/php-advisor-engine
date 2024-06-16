@php
    use Carbon\Carbon;
    use App\Models\SpecialRequest;
    use App\Enums\SpecialRequestStatus;
@endphp
<x-layouts.simple-wrapper>
    <div class="mx-4 flex w-full flex-col gap-4 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5">
        <h1 class="text-center text-2xl tracking-tight text-gray-950 dm-serif">
            @if($booking->restaurant_confirmed_at)
                Thank You for Confirming the Reservation!
            @else
                Confirm Booking Request
            @endif
        </h1>
        
        <livewire:booking.invoice-small :booking="$booking" />
        @if($booking->restaurant_confirmed_at)
            <div class="text-center space-y-4">
                <p>We've notified the guests and reminded them to be on time for their reservation.</p>
                <p class="font-semibold">Thank you for using PRIMA!</p>
            </div>
        @else
            {{ $this->confirmBookingAction }}
        @endif
    </div>
</x-layouts.simple-wrapper>

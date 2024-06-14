@php
    use Carbon\Carbon;
    use App\Models\SpecialRequest;
    use App\Enums\SpecialRequestStatus;
@endphp
<x-layouts.simple-wrapper>
    <div class="flex flex-col w-full gap-4 p-4 mx-4 bg-white rounded-lg shadow-sm ring-1 ring-gray-950/5">
        <h1 class="text-2xl tracking-tight text-center text-gray-950 dm-serif">
            @if($booking->restaurant_confirmed_at)
                Thank You for Confirming the Reservation!
            @else
                Confirm Booking Request
            @endif
        </h1>

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

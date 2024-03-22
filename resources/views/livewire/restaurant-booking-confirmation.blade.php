@php use App\Enums\BookingStatus; @endphp
<div class="flex flex-col justify-center min-h-screen p-6 antialiased wavy-background h-screen">
    <x-filament-panels::logo/>
    <div class="flex flex-col items-center pt-20 flex-grow max-w-lg mx-auto">
        <div class="flex flex-col items-center gap-3" id="form">
            <h1 class="text-3xl text-center sanomat-font">
                Thank You for Confirming the Reservation!
            </h1>

            <h2 class="text-base text-left">
                <p>
                    We've notified the guests and reminded them to be on time for their reservation.
                </p>
                <p class="mt-3 font-semibold">Thank you for using PRIMA!</p>
            </h2>
        </div>
    </div>
    <div class="flex items-end justify-center text-sm text-center">
        &copy; {{ date('Y') }} {{ config('app.name', 'Prima') }}. All rights reserved.
    </div>

</div>

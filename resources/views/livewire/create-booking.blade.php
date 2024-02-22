<div class="min-h-screen antialiased bg-gradient-to-b from-white to-indigo-200 p-6 flex flex-col justify-center">
    <div class="font-extrabold text-xl uppercase text-indigo-800 flex-grow">
        Prima
    </div>
    <div class="max-w-lg mx-auto flex flex-col justify-center">

        <div class="flex flex-col pt-24 items-center gap-4" id="form">
            <h1 class="text-3xl kaisei-opti-regular">Secure Your Reservation</h1>

            <h2 class="text-base text-center">
                Enter your credit card information to confirm your reservation below.
            </h2>

            <div class="flex items-center text-xl font-semibold gap-2">
                <div>Time Remaining:</div>
                <div id="countdown"></div>
            </div>
            
            <x-mary-form wire:submit="save">
                <div class="flex gap-2">
                    <x-mary-input label="First Name" :label="false" placeholder="First Name"/>
                    <x-mary-input label="Last Name" :label="false" placeholder="Last Name"/>
                </div>
                <x-mary-input label="Phone Number" :label="false" placeholder="Cell Phone Number" class="w-full"/>
                <div id="card-element"
                     class="input input-primary w-full flex flex-col justify-center">
                    <!-- A Stripe Element will be inserted here. -->
                </div>

                <x-mary-button class="btn-primary text-white">Complete Reservation</x-mary-button>
            </x-mary-form>
        </div>

        <!-- Invoice -->
        <div class="bg-white flex shadow rounded-xl mt-6 p-6 gap-4 items-center">
            <x-mary-icon name="o-building-storefront" class="w-12 h-12 bg-orange-500 text-white p-2 rounded-full"/>

            <div class="flex flex-col gap-1">
                <div class="font-semibold">{{ $booking->schedule->restaurant->restaurant_name }}</div>
                <div class="text-xs text-slate-600">
                    Tonight {{ $booking->booking_at->format('g:i a') }}
                </div>
            </div>
            <div class="font-semibold flex-grow text-right">
                {{ money($booking->total_fee) }}
            </div>
        </div>

    </div>
    <div class="text-center text-sm flex-grow flex items-end justify-center">
        &copy; {{ date('Y') }} {{ config('app.name', 'Prima') }}. All rights reserved.
    </div>
</div>


@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
@endpush
<script>
    var stripe = Stripe('{{ config('cashier.key') }}');
    var elements = stripe.elements();
    var card = elements.create('card');
    card.mount('#card-element');

    var countdownElement = document.getElementById('countdown');
    var timeLeft = 120; // 2 minutes in seconds

    function countdown() {
        var minutes = Math.floor(timeLeft / 60);
        var seconds = timeLeft % 60;

        countdownElement.innerText = `${ minutes }:${ seconds < 10 ? '0' : '' }${ seconds }`;

        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            countdownElement.innerText = 'Time\'s up!';
        }

        timeLeft--;
    }

    var countdownInterval = setInterval(countdown, 1000);
</script>

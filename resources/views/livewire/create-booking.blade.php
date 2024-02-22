<div
    class="min-h-screen wavy-background antialiased p-6 flex flex-col justify-center">
    <div class="font-extrabold text-2xl text-primary uppercase flex-grow poppins-bold">
        Prima
    </div>
    <div class="max-w-lg mx-auto flex flex-col justify-center">

        @if(!$paymentSuccess)
            <div class="flex flex-col items-center gap-3" id="form">
                <h1 class="text-3xl text-center kaisei-opti-regular">Secure Your Reservation</h1>

                <h2 class="text-base text-center">
                    Enter your credit card information to confirm your reservation below.
                </h2>

                <div class="flex items-center text-xl font-semibold gap-2">
                    <div>Time Remaining:</div>
                    <div id="countdown"></div>
                </div>


                <div class="flex gap-2 w-full">
                    <input type="text" placeholder="First Name" class="input input-primary w-full max-w-xs text-sm"
                           id="first-name-input"/>
                    <input type="text" placeholder="Last Name" class="input input-primary w-full max-w-xs text-sm"
                           id="last-name-input"/>
                </div>

                <input type="text" placeholder="Cell Phone Number" class="input input-primary w-full text-sm"
                       id="phone-input"/>

                <div id="card-element"
                     class="input input-primary w-full flex flex-col justify-center">
                    <!-- A Stripe Element will be inserted here. -->
                </div>

                <x-mary-button
                    :disabled="$isLoading"
                    class="btn-primary text-white w-full"
                    id="submit-button">
                    Complete Reservation
                </x-mary-button>

            </div>
        @else
            <div class="flex flex-col items-center gap-3" id="form">
                <h1 class="text-3xl text-center kaisei-opti-regular">Thank you for your reservation!</h1>

                <h2 class="text-base text-center">
                    Your reservation is confirmed. You will receive a confirmation SMS shortly.
                </h2>
            </div>
        @endif


        <!-- Invoice -->
        <div class="bg-white flex shadow rounded-xl mt-4 p-3 gap-4 items-center">
            <x-mary-icon name="o-building-storefront" class="w-10 h-10 bg-orange-500 text-white p-2 rounded-full"/>

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

@script
<script>
    const stripe = Stripe('{{ config('cashier.key') }}');
    const elements = stripe.elements();
    const card = elements.create('card');
    card.mount('#card-element');

    const submitButton = document.getElementById('submit-button');
    const firstNameInput = document.getElementById('first-name-input');
    const lastNameInput = document.getElementById('last-name-input');
    const phoneInput = document.getElementById('phone-input');

    submitButton.addEventListener('click', async (e) => {
        $wire.$set('isLoading', true);
        const {token, error} = await stripe.createToken(card)

        if (error) {
            $wire.$set('isLoading', false);
            return alert(error.message);
        }

        const form = {
            token,
            firstName: firstNameInput.value,
            lastName: lastNameInput.value,
            phone: phoneInput.value
        }

        $wire.$call('completeBooking', form);

        console.log({token, error});
    });

    // Countdown Timer
    const countdownElement = document.getElementById('countdown');
    let timeLeft = 120; // 2 minutes in seconds

    function countdown() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;

        countdownElement.innerText = `${ minutes }:${ seconds < 10 ? '0' : '' }${ seconds }`;

        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            countdownElement.innerText = 'Time\'s up!';
        }

        timeLeft--;
    }

    const countdownInterval = setInterval(countdown, 1000);
</script>
@endscript

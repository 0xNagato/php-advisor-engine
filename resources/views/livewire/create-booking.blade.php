<div class="flex flex-col justify-center min-h-screen p-6 antialiased wavy-background">
    <x-filament-panels::logo/>
    <div class="flex flex-col items-center justify-center flex-grow max-w-lg mx-auto">

        @if (!$paymentSuccess)
            <div class="flex flex-col items-center gap-3" id="form">
                <h1 class="text-3xl text-center sanomat-font font-semibold">Secure Your Reservation</h1>

                <h2 class="text-base text-center">
                    Enter your credit card information to confirm your reservation below.
                </h2>

                <div class="flex items-center gap-1 text-xl font-semibold">
                    <div>Time Remaining:</div>
                    <div id="countdown">2:00</div>
                </div>


                <div class="flex w-full gap-2">
                    <input
                        required
                        type="text" placeholder="First Name" class="w-full max-w-xs text-sm input input-primary"
                        id="first-name-input" {{ $isLoading ? 'disabled="true"' : '' }}" />
                    <input
                        required
                        type="text" placeholder="Last Name" class="w-full max-w-xs text-sm input input-primary"
                        id="last-name-input" {{ $isLoading ? 'disabled="true"' : '' }}" />
                </div>

                <input required type="text" placeholder="Cell Phone Number" {{ $isLoading ? 'disabled="true"' : '' }}
                class="w-full text-sm input input-primary" id="phone-input"/>

                <div id="card-element" class="flex flex-col justify-center w-full input input-primary">
                    <!-- A Stripe Element will be inserted here. -->
                </div>

                <div id="payment-element">
                    <!-- Mount the Payment Element here -->
                </div>

                {{--                <div class="flex items-center gap-2">--}}
                {{--                    <input type="checkbox" wire:model="agreeTerms" class="checkbox checkbox-primary"/>--}}
                {{--                    <div class="label-text underline font-semibold" @click="$wire.showModal = true">--}}
                {{--                        Accept Terms & Conditions--}}
                {{--                    </div>--}}
                {{--                </div>--}}

                <x-mary-button :disabled="$isLoading" class="w-full text-white btn-primary" id="submit-button">
                    Complete Reservation
                </x-mary-button>

            </div>
        @else
            <div class="flex flex-col items-center gap-3" id="form">
                <h1 class="text-3xl text-center sanomat-font">Thank you for your reservation!</h1>

                <h2 class="text-base text-center">
                    Your reservation is confirmed. You will receive a confirmation SMS shortly.
                </h2>
            </div>
        @endif


        <!-- Invoice -->
        <div class="flex items-center w-full gap-4 p-3 mt-4 bg-white bg-opacity-90 shadow rounded-xl">
            <x-mary-icon name="o-building-storefront" class="w-10 h-10 p-2 text-white bg-orange-500 rounded-full"/>

            <div class="flex flex-col gap-1">
                <div class="font-semibold">{{ $booking->schedule->restaurant->restaurant_name }}</div>
                <div class="text-xs text-slate-600">
                    Tonight {{ $booking->booking_at->format('g:i a') }}
                </div>
            </div>
            <div class="flex-grow font-semibold text-right">
                {{ money($booking->total_fee) }}
            </div>
        </div>

    </div>
    <div class="flex items-end justify-center text-sm text-center">
        &copy; {{ date('Y') }} {{ config('app.name', 'Prima') }}. All rights reserved.
    </div>

    <x-mary-modal wire:model="showModal" title="Terms & Conditions" class="backdrop-blur">
        @markdown(file_get_contents(resource_path('markdown/terms-and-conditions.md')))
        <x-slot:actions>
            <x-mary-button label="Close" @click="$wire.showModal = false"/>
        </x-slot:actions>
    </x-mary-modal>
</div>


@pushOnce('scripts')
    <script src="https://js.stripe.com/v3/"></script>
@endpushonce

@script
<script>
    const stripe = Stripe('{{ config('cashier.key') }}');

    function cardElement() {
        const elements = stripe.elements();
        const card = elements.create('card');
        card.mount('#card-element');
    }

    function paymentElement() {
        const options = {
            mode: 'setup',
            currency: 'usd',
        };
        const elements = stripe.elements(options);
        const paymentElement = elements.create('payment', options);
        paymentElement.mount('#payment-element');
    }

    paymentElement();

    const submitButton = document.getElementById('submit-button');
    const firstNameInput = document.getElementById('first-name-input');
    const lastNameInput = document.getElementById('last-name-input');
    const phoneInput = document.getElementById('phone-input');

    // submitButton.addEventListener('click', async (_e) => {
    //     $wire.$set('isLoading', true);
    //     const {
    //         token,
    //         error
    //     } = await stripe.createToken(card)
    //
    //     if (error) {
    //         $wire.$set('isLoading', false);
    //         return alert(error.message);
    //     }
    //
    //     const form = {
    //         token,
    //         firstName: firstNameInput.value,
    //         lastName: lastNameInput.value,
    //         phone: phoneInput.value
    //     }
    //
    //     $wire.$call('completeBooking', form);
    // });

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

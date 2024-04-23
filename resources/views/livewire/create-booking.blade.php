@php use App\Enums\BookingStatus; @endphp
<div class="flex flex-col justify-center min-h-screen p-4 antialiased wavy-background h-screen">
    <x-filament-panels::logo/>
    <div class="flex flex-col items-center pt-10 flex-grow max-w-lg mx-auto">
        {{--    <div class="flex flex-col items-center pt-20 sm:pt-0 sm:justify-center flex-grow max-w-lg mx-auto">--}}
        @if($booking->status === BookingStatus::CONFIRMED)
            <div class="flex flex-col items-center gap-3" id="form">
                <h1 class="text-3xl text-center dm-serif">Thank you for your reservation!</h1>

                <h2 class="text-base text-center">
                    <p>Your reservation request has been received. Please check your phone for a text confirmation.</p>
                    <p>We are notifying the restaurant now.</p>
                    <p class="mt-3 font-semibold">Thank you for using PRIMA!</p>
                </h2>
            </div>
        @elseif(!$this->isValid())
            <div class="flex flex-col items-center gap-3" id="form">
                <h1 class="text-3xl text-center dm-serif">Sorry!</h1>

                <h2 class="text-base text-left">
                    <p>
                        Sorry, this payment link is expired. Please consult with your PRIMA Concierge to request a new
                        payment link.
                    </p>
                    <p class="mt-3 font-semibold">Thank you for using PRIMA!</p>
                </h2>
            </div>
        @elseif ($this->isValid())
            <div class="flex flex-col items-center gap-3">
                <h1 class="text-3xl text-center dm-serif font-semibold">Secure Your Reservation</h1>

                <h2 class="text-base text-center">
                    Enter your credit card information to confirm your reservation below.
                </h2>

                <div class="flex items-center gap-1 text-xl font-semibold">
                    <div>Time Remaining:</div>

                    <span id="countdown" class="font-mono text-xl">

                    </span>
                </div>


                <form id="form" class="w-full">
                    <fieldset
                        {{ $isLoading ? 'disabled' : '' }} class="flex flex-col items-center gap-2 disabled:opacity-50">
                        <div class="flex w-full gap-2 items-center">
                            <label class="w-full">
                                <input
                                    name="first_name"
                                    type="text"
                                    class="w-full rounded-lg border border-indigo-600 text-sm h-[40px]"
                                    placeholder="First Name"
                                    required
                                >
                            </label>

                            <label class="w-full">
                                <input
                                    name="last_name"
                                    type="text"
                                    class="w-full rounded-lg border border-indigo-600 text-sm h-[40px]"
                                    placeholder="Last Name"
                                    required
                                >
                            </label>

                        </div>

                        <label class="w-full">
                            <input
                                name="phone"
                                type="text"
                                class="w-full rounded-lg border border-indigo-600 text-sm h-[40px]"
                                placeholder="Cell Phone Number"
                                required
                            >
                        </label>

                        <label class="w-full">
                            <input name="email" type="email"
                                   class="w-full rounded-lg border border-indigo-600 text-sm h-[40px]"
                                   placeholder="Email Address (optional)">
                        </label>

                        @if ($booking->prime_time)
                            <div id="card-element"
                                 wire:ignore
                                 class="w-full rounded-lg border border-indigo-600 text-sm bg-white px-2 py-3 h-[40px]">
                                <!-- A Stripe Element will be inserted here. -->
                            </div>
                        @endif

                        <div class="flex items-center gap-2">
                            <label class="text-[11px] flex items-center gap-1">
                                <x-filament::input.checkbox checked name="agree"/>
                                <span>I agree to receive my reservation confirmation via text message.</span>
                            </label>
                        </div>

                        <x-filament::button class="w-full" type="submit" color="indigo" size="xl">
                            Complete Reservation
                        </x-filament::button>

                        <div class="font-semibold mt-1 text-sm text-center">
                            Fees paid are for reservation only. Not applicable towards restaurant bill.
                        </div>
                    </fieldset>
                </form>
            </div>
        @endif


        <!-- Invoice -->
        <div class="w-full mt-4">
            <livewire:booking.invoice-small :booking="$booking"/>
        </div>

    </div>
    <div class="flex items-end justify-center text-sm text-center">
        &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
    </div>
</div>


@pushOnce('scripts')
    <script src="https://js.stripe.com/v3/"></script>
@endpushonce

@script
<script>
    let countdownDiv = document.getElementById('countdown');
    let count = 300; // 5 minutes in seconds

    setInterval(() => {
        count--;
        let minutes = Math.floor(count / 60);
        let seconds = count % 60;
        countdownDiv.innerHTML = `${ minutes }:${ seconds.toString().padStart(2, '0') }`;
        if (count === 0) {
            clearInterval();
        }
    }, 1000);

    const isPrimeTime = @JS($booking->prime_time);

    if (isPrimeTime) {
        const stripe = Stripe('{{ config('services.stripe.key') }}');

        const elements = stripe.elements();
        const card = elements.create('card', {
            disableLink: true,
            hidePostalCode: true,
        });

        card.mount('#card-element');
    }

    const form = document.getElementById('form');

    form.addEventListener('submit', async (e) => {
        const agreeCheckbox = document.querySelector('input[name=agree]');
        if (!agreeCheckbox.checked) {
            alert('You must agree to receive your reservation confirmation via text message.');
            e.preventDefault();
            return;
        }
        e.preventDefault();

        $wire.$set('isLoading', true);

        if (isPrimeTime) {
            const {
                token,
                error
            } = await stripe.createToken(card)

            if (error) {
                $wire.$set('isLoading', false);
                card.update({disabled: false});
                return alert(error.message);
            }
        }

        const formData = {
            first_name: document.querySelector('input[name="first_name"]').value,
            last_name: document.querySelector('input[name="last_name"]').value,
            phone: document.querySelector('input[name="phone"]').value,
            email: document.querySelector('input[name="email"]').value,
            token: isPrimeTime ? token.id : null
        }

        $wire.$call('completeBooking', formData);
    });
</script>
@endscript

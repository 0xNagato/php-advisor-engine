@php use App\Enums\BookingStatus;use Carbon\Carbon; @endphp
    <!--suppress JSUnresolvedReference, BadExpressionStatementJS -->
<x-filament-widgets::widget x-data="{}" x-init="() => {
    let script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    document.head.appendChild(script);
}" class="flex flex-col gap-4">
    @if (!$booking)
        {{--        @env('local')--}}
        {{--            <pre class="text-xs">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>--}}
        {{--        @endenv--}}
        {{ $this->form }}

        <div class="flex flex-col gap-2">
            @if ($this->schedulesToday->count() || $this->schedulesThisWeek->count())

                @if ($this->schedulesToday->count())

                    <div class="grid gap-2 grid-cols-3">
                        @foreach ($this->schedulesToday as $schedule)
                            <button @if ($schedule->is_bookable) wire:click="createBooking({{ $schedule->id }})" @endif
                                @class([
                                    'flex flex-col gap-1 items-center p-3 text-sm font-semibold leading-none rounded-xl',
                                    'outline outline-2 outline-offset-2 outline-green-600' => $schedule->start_time === $this->data['reservation_time'],
                                    'outline outline-2 outline-offset-2 outline-gray-100' => $schedule->start_time === $this->data['reservation_time'] && !$schedule->is_bookable,
                                    'outline outline-2 outline-offset-2 outline-indigo-600' => $schedule->start_time === $this->data['reservation_time'] && $schedule->prime_time,
                                    'bg-green-600 text-white cursor-pointer hover:bg-green-500' => $schedule->is_bookable,
                                    'bg-gray-100 text-gray-400 border-none' => !$schedule->is_bookable,
                                ])
                            >
                                <div class="text-center text-lg">
                                    {{ $schedule->formatted_start_time }}
                                </div>
                                <div>
                                    {{ $schedule->is_bookable ? money($schedule->fee) : money(0)}}
                                </div>
                            </button>
                        @endforeach
                    </div>

                @endif

                @if ($this->schedulesThisWeek->count())
                    <div class="font-bold text-sm text-center uppercase mt-2">
                        Next {{ self::AVAILABILITY_DAYS }} Days Availability
                    </div>
                    <div class="grid gap-2 grid-cols-3">
                        @foreach ($this->schedulesThisWeek as $schedule)
                            <div
                                @if ($schedule->is_bookable)
                                    wire:click="createBooking({{ $schedule->id }}, '{{ $schedule->booking_date->format('Y-m-d') }}')"
                                @endif
                                @class([
                                    'flex flex-col gap-1 items-center px-3 py-3 text-sm font-semibold leading-none rounded-xl',
                                    'bg-green-600 text-white cursor-pointer hover:bg-green-500' => $schedule->is_bookable,
                                    'bg-gray-100 text-gray-400' => !$schedule->is_bookable,
                                ])
                            >
                                <div class="text-xs text-center">
                                    {{ $schedule->booking_date->format('D, M jS') }}
                                </div>
                                <div class="text-center text-lg">
                                    {{ $schedule->formatted_start_time }}
                                </div>
                                <div>
                                    {{ $schedule->is_bookable ? money($schedule->fee) : money(0)}}
                                </div>
                            </div>
                        @endforeach
                    </div>

                @endif

            @endif
        </div>

    @endif


    @if ($booking && (BookingStatus::PENDING === $booking->status || BookingStatus::GUEST_ON_PAGE === $booking->status))

        @env('local')
            <x-filament::button tag="a" :href="$bookingUrl" target="_new">
                Customer Booking Link
            </x-filament::button>
        @endenv


        <div x-data="{ tab: 'collectPayment' }" id="tabs">

            <div class="flex space-x-4">
                <div class="flex w-full space-x-4 text-xs">
                    <button
                        :class="{ 'bg-indigo-600 text-white': tab === 'collectPayment', 'bg-gray-100': tab !== 'collectPayment' }"
                        @click="tab = 'collectPayment'"
                        class="flex items-center gap-1 px-4 py-2 text-xs font-semibold bg-gray-100 rounded-lg shadow-lg shadow-gray-400">
                        <x-gmdi-credit-card class="w-6 h-6 font-semibold text-center"/>
                        <div>Credit Card</div>
                    </button>
                    <button
                        :class="{ 'bg-indigo-600 text-white': tab === 'smsPayment', 'bg-gray-100': tab !== 'smsPayment' }"
                        @click="tab = 'smsPayment'"
                        class="flex items-center gap-1 px-4 py-2 text-xs font-semibold bg-gray-100 rounded-lg shadow-lg shadow-gray-400">
                        <x-gmdi-phone-android-r class="w-6 h-6 font-semibold"/>
                        <div>SMS</div>
                    </button>
                    <button
                        :class="{ 'bg-indigo-600 text-white': tab === 'qrCode', 'bg-gray-100': tab !== 'qrCode' }"
                        @click="tab = 'qrCode'"
                        class="flex items-center gap-1 px-4 py-2 text-xs font-semibold bg-gray-100 rounded-lg shadow-lg shadow-gray-400">
                        <x-gmdi-qr-code class="w-6 h-6 font-semibold"/>
                        <div>QR Code</div>
                    </button>
                </div>
            </div>

            <div x-show="tab === 'collectPayment'" class="mt-6">
                <!-- @todo Refactor this to a separate component -->
                <div wire:ignore class="flex flex-col items-center gap-3" x-data="{}"
                     x-init="() => {
                        function initializeStripe() {
                            if (window.Stripe) {
                                setupStripe();
                            } else {
                                setTimeout(initializeStripe, 10);
                            }
                        }

                        function setupStripe() {
                            const stripe = Stripe('{{ config('services.stripe.key') }}');
                            const elements = stripe.elements();
                            const card = elements.create('card', {
                                disableLink: true,
                                hidePostalCode: true
                            });
                            card.mount('#card-element');

                            const form = document.getElementById('form');

                            form.addEventListener('submit', async (e) => {
                                //const agreeCheckbox = document.querySelector('input[name=agree]');
                                //if (!agreeCheckbox.checked) {
                                //   alert('You must agree to receive your reservation confirmation via text message.');
                                //  e.preventDefault();
                                // return;
                                //}
                                e.preventDefault();
                                $wire.$set('isLoading', true);

                                const { token, error } = await stripe.createToken(card);

                                if (error) {
                                    $wire.$set('isLoading', false);
                                    return;
                                }

                                const formData = {
                                    first_name: document.querySelector('input[name=first_name]').value,
                                    last_name: document.querySelector('input[name=last_name]').value,
                                    phone: document.querySelector('input[name=phone]').value,
                                    email: document.querySelector('input[name=email]').value,
                                    token: token.id
                                }

                                $wire.$call('completeBooking', formData);
                            });

                        }

                        initializeStripe();
                    }">

                    <form id="form" class="w-full">
                        <fieldset class="flex flex-col items-center gap-2 disabled:opacity-50">
                            <div class="flex items-center w-full gap-2">
                                <label class="w-full">
                                    <input name="first_name" type="text"
                                           class="w-full rounded-lg border border-gray-400 text-sm h-[40px]"
                                           placeholder="First Name" required>
                                </label>

                                <label class="w-full">
                                    <input name="last_name" type="text"
                                           class="w-full rounded-lg border border-gray-400 text-sm h-[40px]"
                                           placeholder="Last Name" required>
                                </label>

                            </div>

                            <label class="w-full">
                                <input name="phone" type="text"
                                       class="w-full rounded-lg border border-gray-400 text-sm h-[40px]"
                                       placeholder="Cell Phone Number" required>
                            </label>

                            <label class="w-full">
                                <input name="email" type="email"
                                       class="w-full rounded-lg border border-gray-400 text-sm h-[40px]"
                                       placeholder="Email Address (optional)">
                            </label>

                            <div id="card-element"
                                 class="w-full rounded-lg border border-gray-400 text-sm bg-white px-2 py-3 h-[40px]">
                                <!-- A Stripe Element will be inserted here. -->
                            </div>

                            {{--                            <div class="flex items-center gap-2"> --}}
                            {{--                                <label class="text-[11px] flex items-center gap-1"> --}}
                            {{--                                    <x-filament::input.checkbox checked name="agree"/> --}}
                            {{--                                    <span>I agree to receive my reservation confirmation via text message.</span> --}}
                            {{--                                </label> --}}
                            {{--                            </div> --}}

                            <x-filament::button class="w-full" type="submit" size="xl">
                                Complete Reservation
                            </x-filament::button>
                        </fieldset>
                    </form>

                    <div class="w-full">
                        <livewire:booking.invoice-small :booking="$booking"/>
                    </div>

                </div>
            </div>

            <div x-show="tab === 'smsPayment'" class="flex flex-col gap-4 mt-4">
                <!-- SMS Payment Link Tab Content -->
                @if ($SMSSent)
                    <div class="flex flex-col gap-2 p-4 bg-white rounded shadow">
                        <p>Advise customer to check their phone for reservation payment link.</p>
                        <p>Sending message to customer now.</p>
                    </div>
                @endif
                @php
                    $message = "Your reservation at {$booking->restaurant->restaurant_name} is pending. Please click $bookingUrl to secure your booking within the next 5 minutes.";
                @endphp
                <livewire:s-m-s-input :message="$message"/>
            </div>

            <div x-show="tab === 'qrCode'" class="flex flex-col gap-4 mt-4">
                <!-- QR Code Tab Content -->
                <div class="mb-2 text-base font-semibold">
                    Show QR code below to customer to accept secure payment.
                </div>

                <img src="{{ $qrCode }}" alt="QR Code" class="w-1/2 mx-auto shadow-lg">

                <livewire:booking.booking-status-widget :booking="$booking"/>
            </div>
        </div>

        <x-filament::button wire:click="cancelBooking" class="w-full opacity-50" color="gray">
            Abandon Reservation
        </x-filament::button>
    @elseif($booking && $booking->status === BookingStatus::CONFIRMED)
        <div class="flex flex-col items-center gap-3" id="form">
            <div class="text-xl font-semibold text-black divider divider-neutral">Reservation Confirmed</div>
            <p>Thank you for the booking! We are notifying the restaurant now.</p>
        </div>

        <x-filament::button wire:click="resetBooking" class="w-full bg-[#421fff] h-[48px]" icon="gmdi-restaurant-menu">
            Back to Reservation Hub
        </x-filament::button>

        <div class="flex gap-4">

            <x-filament::button tag="a" class="w-1/2" color="gray"
                                :href="route('filament.admin.resources.bookings.view', ['record' => $booking])">
                View Booking
            </x-filament::button>

            <x-filament::button tag="a" class="w-1/2" color="gray"
                                :href="route('customer.invoice', ['token' => $booking->uuid])">
                View Invoice
            </x-filament::button>

        </div>
    @endif

</x-filament-widgets::widget>

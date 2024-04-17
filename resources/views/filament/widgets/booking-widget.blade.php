@php use App\Enums\BookingStatus;use Carbon\Carbon; @endphp
    <!--suppress JSUnresolvedReference, BadExpressionStatementJS -->
<x-filament-widgets::widget x-data="{}" x-init="() => {
    let script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    document.head.appendChild(script);
}" class="flex flex-col gap-4">
    @if (!$booking)
        {{ $this->form }}

        @if ($this->schedulesToday->count() || $this->schedulesThisWeek->count())
            <div class="flex flex-col bg-white border divide-y rounded-lg shadow">
                @if ($this->schedulesToday->count())
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="text-sm font-semibold mb-2 flex-grow">
                                Availability {{ formatDateFromString($this->data['date']) }}
                            </div>
                            <div class="flex items-center gap-1">
                                <x-heroicon-s-star @class(['h-4 w-4 text-yellow-300 -mt-0.5']) />
                                <span class="font-semibold text-xs">PRIME</span>
                            </div>
                        </div>
                        <div class="grid gap-1.5 grid-cols-3">
                            @foreach ($this->schedulesToday as $schedule)
                                <div @if ($schedule->is_bookable) wire:click="createBooking({{ $schedule->id }})" @endif
                                class="flex gap-1 items-center px-2 py-2 text-xs font-semibold leading-none rounded-full {{ $schedule->is_bookable ? 'bg-green-600 text-white cursor-pointer' : 'bg-gray-100 text-gray-400' }}">
                                    @if (!$schedule->prime_time)
                                        {{-- <x-heroicon-s-currency-dollar class="h-4 w-4" /> --}}
                                    @else
                                        <x-heroicon-s-star @class(['h-4 w-4', 'text-yellow-300' => $schedule->is_bookable]) />
                                    @endif
                                    <span>{{ $schedule->formatted_start_time }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->schedulesThisWeek->count())
                    <div class="p-4">
                        <div class="text-sm font-semibold mb-2">Availability later this week</div>
                        <div class="grid gap-1.5 grid-cols-2">
                            @foreach ($this->schedulesThisWeek as $schedule)
                                @php
                                    $nextDayOfWeek = Carbon::parse(
                                        $this->data['date'],
                                        auth()->user()->timezone,
                                    )->next($schedule->day_of_week);
                                @endphp
                                <div
                                    @if ($schedule->is_bookable) wire:click="createBooking({{ $schedule->id }}, '{{ $nextDayOfWeek->format('Y-m-d') }}')"
                                    @endif
                                    class="flex gap-1 items-center px-2 py-2 text-xs font-semibold leading-none rounded-full {{ $schedule->is_bookable ? 'bg-green-600 text-white cursor-pointer' : 'bg-gray-100 text-gray-400' }}">
                                    {{-- <x-heroicon-s-currency-dollar class="h-4 w-4" /> --}}
                                    <span>
                                        {{-- {{ substr(ucfirst($schedule->day_of_week), 0, 3) }}, --}}
                                        {{ $nextDayOfWeek->format('M jS') }}
                                        {{ $schedule->formatted_start_time }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- <x-filament::button wire:click="createBooking" wire:loading.attr="disabled" class="w-full bg-[#421fff] h-[48px]"
            icon="gmdi-restaurant-menu">
            Hold Reservation
        </x-filament::button> --}}
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

@php use App\Enums\BookingStatus;use App\Livewire\Booking\BookingWidget;use Carbon\Carbon; @endphp
    <!--suppress JSUnresolvedReference, BadExpressionStatementJS -->
<x-filament-widgets::widget x-data="{}" x-init="() => {
    let script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    document.head.appendChild(script);
}" class="flex flex-col gap-4">
    @if (!$booking)
        {{ $this->form }}

        <div class="flex flex-col gap-2">
            @if ($schedulesToday->count() || $schedulesThisWeek->count())
                @if ($schedulesToday->count())

                    <div class="grid gap-2 grid-cols-3">
                        @foreach ($schedulesToday as $schedule)
                            <button
                                @if ($schedule->is_bookable) wire:click="createBooking({{ $schedule->schedule_template_id }})" @endif
                                @class([
                                    'flex flex-col gap-1 items-center p-3 text-sm font-semibold leading-none rounded-xl justify-center',
                                    'outline outline-2 outline-offset-2 outline-green-600' => $schedule->start_time === $data['reservation_time'],
                                    'outline outline-2 outline-offset-2 outline-gray-100' => $schedule->start_time === $data['reservation_time'] && !$schedule->is_bookable,
                                    'bg-green-600 text-white cursor-pointer hover:bg-green-500' => $schedule->is_bookable,
                                    'bg-gray-100 text-gray-400 border-none' => !$schedule->is_bookable,
                                ])
                            >
                                <div class="text-center text-lg">
                                    {{ $schedule->formatted_start_time }}
                                </div>
                                <div>
                                    @if($schedule->is_bookable && $schedule->prime_time)
                                        {{ money($schedule->fee($data['guest_count'])) }}
                                    @elseif($schedule->is_bookable && !$schedule->prime_time)
                                        No Fee
                                    @endif
                                </div>
                                @if($schedule->is_bookable && $schedule->remaining_tables <= 5)
                                    <div
                                        class="bg-red-500 [text-shadow:_0_1px_0_rgb(0_0_0_/_40%)] mt-1 px-2 py-1 border border-red-900 text-white text-[12px] text-nowrap rounded">
                                        Last Tables ({{ $schedule->remaining_tables }})
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>

                @endif

                @if ($schedulesThisWeek->count())
                    <div class="font-bold text-sm text-center uppercase mt-2">
                        Next {{ self::AVAILABILITY_DAYS }} Days Availability
                    </div>
                    <div class="grid gap-2 grid-cols-3">
                        @foreach ($schedulesThisWeek as $schedule)
                            <div
                                @if ($schedule->is_bookable)
                                    wire:click="createBooking({{ $schedule->schedule_template_id }}, '{{ $schedule->booking_date->format('Y-m-d') }}')"
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
                                    @if($schedule->is_bookable && $schedule->prime_time)
                                        {{ money($schedule->fee($data['guest_count'])) }}
                                    @elseif($schedule->is_bookable && !$schedule->prime_time)
                                        No Fee
                                    @endif
                                </div>
                                @if($schedule->is_bookable && $schedule->remaining_tables <= 5)
                                    <div
                                        class="bg-red-500 [text-shadow:_0_1px_0_rgb(0_0_0_/_40%)] mt-1 px-2 py-1 border border-red-900 text-white text-[12px] text-nowrap rounded">
                                        Last Tables ({{ $schedule->remaining_tables }})
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                @endif

            @endif
        </div>

    @endif


    @if ($booking && (BookingStatus::PENDING === $booking->status || BookingStatus::GUEST_ON_PAGE === $booking->status))

        <div x-data="{ tab: '{{ $booking->prime_time ? 'smsPayment' : 'collectPayment' }}' }" id="tabs">
            @if($booking->prime_time)
                <div class="flex space-x-4">
                    <div class="flex w-full space-x-4 text-xs">
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
                        <button
                            :class="{ 'bg-indigo-600 text-white': tab === 'collectPayment', 'bg-gray-100': tab !== 'collectPayment' }"
                            @click="tab = 'collectPayment'"
                            class="flex items-center gap-1 px-4 py-2 text-xs font-semibold bg-gray-100 rounded-lg shadow-lg shadow-gray-400">
                            <x-gmdi-credit-card class="w-6 h-6 font-semibold text-center"/>
                            <div>Collect CC</div>
                        </button>
                    </div>
                </div>
            @else
                <div class="-mt-6"></div>
            @endif

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
                                e.preventDefault();
                                $wire.$set('isLoading', true);


                                @if($booking->prime_time)
                                const { token, error } = await stripe.createToken(card);

                                if (error) {
                                    $wire.$set('isLoading', false);
                                    return;
                                }
                                @else
                                var token = {id: '' };
                                @endif

                                const formData = {
                                    first_name: document.querySelector('input[name=first_name]').value,
                                    last_name: document.querySelector('input[name=last_name]').value,
                                    phone: document.querySelector('input[name=phone]').value,
                                    email: document.querySelector('input[name=email]').value,
                                    token: token?.id ?? ''
                                }

                                $wire.$call('completeBooking', formData);
                            })

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
                                 class="w-full rounded-lg border border-gray-400 text-sm bg-white px-2 py-3 h-[40px] {{ !$booking->prime_time ? 'hidden' : ''  }}">
                                <!-- A Stripe Element will be inserted here. -->
                            </div>


                            <x-filament::button class="w-full" type="submit" size="xl">
                                Complete Reservation
                            </x-filament::button>
                        </fieldset>
                    </form>
                </div>
            </div>

            <div x-show="tab === 'smsPayment'" class="flex flex-col gap-4 mt-4">
                <!-- SMS Payment Link Tab Content -->
                <livewire:booking.s-m-s-booking-form :booking="$booking" :booking-url="$bookingUrl"/>
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

        <div class="w-full">
            <livewire:booking.invoice-small :booking="$booking"/>
        </div>

        <x-filament::button wire:click="cancelBooking" class="w-full opacity-50" color="gray">
            Abandon Reservation
        </x-filament::button>
    @elseif($booking && $booking->status === BookingStatus::CONFIRMED)
        <div class="flex flex-col items-center gap-3" id="form">
            <div class="text-xl font-semibold text-black divider divider-neutral">Reservation Confirmed</div>
            <p class="text-center">Thank you for the booking!<br>We are notifying the restaurant now.</p>
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

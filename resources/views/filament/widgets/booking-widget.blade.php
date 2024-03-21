@php use App\Enums\BookingStatus;use App\Livewire\InvoiceSmall; @endphp
    <!--suppress JSUnresolvedReference, BadExpressionStatementJS -->
<x-filament-widgets::widget
    x-data="{}"
    x-init="() => {
        let script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        document.head.appendChild(script);
    }"
    class="flex flex-col gap-4"
>

    @if (!$booking)
        <div x-data="{
            showCalendar: false,
            today: new Date().toISOString().split('T')[0],
            tomorrow: new Date(new Date().setDate(new Date().getDate() + 1)).toISOString().split('T')[0]
        }">

            <div class="flex space-x-4" x-bind:class="{ 'mb-4': showCalendar, 'mb-2': !showCalendar }">
                <label class="inline-flex items-center">
                    <input checked type="radio" class="form-radio" name="date" value="today"
                           @click="showCalendar = false; $wire.set('selectedDate', today)">
                    <span class="ml-2">Today</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" class="form-radio" name="date" value="tomorrow"
                           @click="showCalendar = false; $wire.set('selectedDate', tomorrow)">
                    <span class="ml-2">Tomorrow</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" class="form-radio" name="date" value="calendar"
                           @click="showCalendar = true">
                    <span class="ml-2">Select Date</span>
                </label>
            </div>

            <x-filament::input.wrapper x-show="showCalendar" @click="$refs.calendar.focus()"
                                       suffix-icon="heroicon-m-calendar">
                <x-filament::input type="date" x-ref="calendar" wire:model.live="selectedDate"/>
            </x-filament::input.wrapper>

        </div>

        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="selectedRestaurantId">
                <option value="">Select a restaurant</option>
                @foreach ($restaurants as $restaurant)
                    <option value="{{ $restaurant->id }}">{{ $restaurant->restaurant_name }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        @if($unavailableSchedules && $unavailableSchedules->isNotEmpty())
            <div class="bg-red-50 border border-red-400 text-red-700 p-4 rounded relative text-xs" role="alert">
                <div class="font-bold mb-2">Unavailable Times:</div>
                <div class="grid grid-cols-4 gap-x-4">
                    @foreach($unavailableSchedules as $schedule)
                        <div>{{ $schedule->start_time->format('g:i a') }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="selectedScheduleId" :disabled="!$selectedRestaurantId">
                <option value="">Select a time</option>
                @foreach ($schedules ?? [] as $schedule)
                    <option value="{{ $schedule->id }}">{{ $schedule->start_time->format('g:i a') }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>


        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="guestCount" :disabled="!$selectedScheduleId">
                <option value="">How many guests?</option>
                @for ($i = 2; $i <= 8; $i++)
                    @php
                        $price = 200 + ($i - 2) * 50;
                    @endphp
                    <option value="{{ $i }}">{{ $i }} Guests (${{ $price }})</option>
                @endfor
            </x-filament::input.select>
        </x-filament::input.wrapper>


        <x-filament::button wire:click="createBooking" class="w-full bg-[#421fff] h-[48px]" :disabled="!$guestCount"
                            icon="gmdi-restaurant-menu">
            Hold Reservation
        </x-filament::button>

    @endif


    @if ($booking && (BookingStatus::PENDING === $booking->status || BookingStatus::GUEST_ON_PAGE === $booking->status))
        <livewire:invoice-small :booking="$booking"/>

        @env('local')
            {{ $bookingUrl }}
        @endenv

        <div x-data="{ tab: 'collectPayment' }">
            <div class="flex space-x-4">
                <button :class="{ 'border-[#4736dd] text-[#4736dd] bg-white': tab === 'collectPayment' }"
                        @click="tab = 'collectPayment'"
                        class="border px-2 py-1 text-sm font-semibold rounded">Collect Payment
                </button>
                <button :class="{ 'border-[#4736dd] text-[#4736dd] bg-white': tab === 'smsPayment' }"
                        @click="tab = 'smsPayment'"
                        class="border px-2 py-1 text-sm font-semibold rounded">SMS Payment Link
                </button>
                <button :class="{ 'border-[#4736dd] text-[#4736dd] bg-white': tab === 'qrCode' }"
                        @click="tab = 'qrCode'"
                        class="border px-2 py-1 text-sm font-semibold rounded">QR Code
                </button>
            </div>

            <div x-show="tab === 'collectPayment'" class="mt-4">
                <!-- Collect Payment Tab Content -->
                <div class="text-base font-semibold mb-2">
                    Please Enter Reservation Details
                </div>

                <!-- @todo Refactor this to a separate component -->
                <div class="flex flex-col items-center gap-3" x-data="{}" x-init="() => {
                    function initializeStripe() {
                        if (window.Stripe) {
                            setupStripe();
                        } else {
                            setTimeout(initializeStripe, 10);
                        }
                    }

                    function setupStripe() {
                        const stripe = Stripe('{{ config('cashier.key') }}');
                        const elements = stripe.elements();
                        const card = elements.create('card', {
                            disableLink: true,
                            hidePostalCode: true
                        });
                        card.mount('#card-element');

                        const form = document.getElementById('form');

                        form.addEventListener('submit', async (e) => {
                            e.preventDefault();
                            card.update({disabled: true});
                            $wire.$set('isLoading', true);

                            const {token, error} = await stripe.createToken(card);

                            if (error) {
                                card.update({disabled: false});
                                $wire.$set('isLoading', false);
                                alert(error.message);
                            }

                            const formData = {
                                first_name: document.querySelector('input[name=first_name]').value,
                                last_name: document.querySelector('input[name=last_name]').value,
                                phone: document.querySelector('input[name=phone]').value,
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
                                           class="w-full rounded-lg border border-indigo-600 text-sm h-[40px]"
                                           placeholder="First Name" required>
                                </label>

                                <label class="w-full">
                                    <input name="last_name" type="text"
                                           class="w-full rounded-lg border border-indigo-600 text-sm h-[40px]"
                                           placeholder="Last Name" required>
                                </label>

                            </div>

                            <label class="w-full">
                                <input name="phone" type="text"
                                       class="w-full rounded-lg border border-indigo-600 text-sm h-[40px]"
                                       placeholder="Cell Phone Number" required>
                            </label>

                            <div id="card-element"
                                 class="w-full rounded-lg border border-indigo-600 text-sm bg-white px-2 py-3 h-[40px]">
                                <!-- A Stripe Element will be inserted here. -->
                            </div>

                            <x-filament::button class="w-full" type="submit" color="indigo" size="xl">
                                Complete Reservation
                            </x-filament::button>
                        </fieldset>
                    </form>

                </div>
            </div>

            <div x-show="tab === 'smsPayment'" class="mt-4 flex flex-col gap-4">
                <!-- SMS Payment Link Tab Content -->
                @if($SMSSent)
                    <div class="flex flex-col gap-2 bg-white rounded shadow p-4">
                        <p>Please advise customer to check their phone for reservation payment link.</p>
                        <p>Sending message to customer now.</p>
                    </div>
                @endif
                @php
                    $message = "Your reservation at {$booking->restaurant->restaurant_name} is pending. Please click {$bookingUrl} to secure your booking within the next 5 minutes.";
                @endphp
                <livewire:s-m-s-input :message="$message"/>
            </div>

            <div x-show="tab === 'qrCode'" class="mt-4 flex flex-col gap-4">
                <!-- QR Code Tab Content -->
                <div class="text-base font-semibold mb-2">
                    Show QR code below to customer to accept secure payment.
                </div>

                <img src="{{ $qrCode }}" alt="QR Code" class="w-1/2 mx-auto shadow-lg">

                <livewire:booking-status-widget :booking="$booking"/>
            </div>
        </div>



        <x-mary-button wire:click="cancelBooking" class="border-none btn bg-slate-300 text-slate-600">
            Abandon Reservation
        </x-mary-button>

    @elseif($booking && $booking->status === BookingStatus::CONFIRMED)
        <div class="flex flex-col items-center gap-3" id="form">
            <div class="text-xl font-semibold text-black divider divider-neutral">Reservation Confirmed</div>
            <p>Thank you for the booking! We are notifying the restaurant now.</p>
        </div>

        <x-mary-button wire:click="cancelBooking" class="btn bg-[#421fff] text-white">
            Create New Booking
        </x-mary-button>
    @endif

</x-filament-widgets::widget>

@php use App\Enums\BookingStatus; @endphp
    <!--suppress JSUnresolvedReference, BadExpressionStatementJS -->
<x-filament-widgets::widget x-data="{}" x-init="() => {
    let script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    document.head.appendChild(script);
}" class="flex flex-col gap-4">

    @if (!$booking)
        <div x-data="{
            showCalendar: false,
            today: new Date().toISOString().split('T')[0],
            tomorrow: new Date(new Date().setDate(new Date().getDate() + 1)).toISOString().split('T')[0]
        }">

            <div class="flex space-x-4" x-bind:class="{ 'mb-4': showCalendar, 'mb-2': !showCalendar }">
                <label class="inline-flex items-center">
                    <input checked type="radio" class="form-radio" name="date" value="today"
                           @click="showCalendar = false; $wire.selectedDate = today">
                    <span class="ml-2">Today</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" class="form-radio" name="date" value="tomorrow"
                           @click="showCalendar = false; $wire.selectedDate = tomorrow">
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
                <x-filament::input type="date" x-ref="calendar" wire:model="selectedDate"/>
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


        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="selectedScheduleId" :disabled="!$selectedRestaurantId">
                <option value="">Select a time</option>
                @foreach ($selectedRestaurant?->schedules ?? [] as $schedule)
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


    @if ($booking && BookingStatus::PENDING === $booking->status)
        <!-- Invoice -->
        <div class="flex items-center w-full gap-4 p-3 bg-white bg-opacity-90 shadow rounded-xl">
            <x-mary-icon name="o-building-storefront" class="w-10 h-10 p-2 text-white bg-orange-500 rounded-full"/>

            <div class="flex flex-col gap-1">
                <div class="font-semibold">{{ $booking->schedule->restaurant->restaurant_name }}</div>
                <div class="text-xs text-slate-600">
                    Tonight {{ $booking->booking_at->format('g:i a') }}
                </div>
            </div>
            <div class="flex-grow flex text-right flex-col gap-1">
                <div class="font-semibold">
                    {{ money($booking->total_fee) }}
                </div>
                <div class="text-xs text-slate-600">
                    ({{ $booking->guest_count }} Guests)
                </div>
            </div>
        </div>

        <div class="text-base font-semibold">
            Please Enter Reservation Details
        </div>

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

        <div class="text-xl font-semibold text-black divider divider-neutral">OR</div>

        <div class="text-base">
            Show QR code below to customer to accept secure payment.
        </div>

        <img src="{{ $qrCode }}" alt="QR Code" class="w-1/2 mx-auto shadow-lg">

        <livewire:booking-status-widget :booking="$booking"/>

        <x-mary-button external :link="$bookingUrl" class="btn bg-[#421fff] text-white" icon="o-credit-card">
            Collect Guest's Credit Card
        </x-mary-button>

        <x-mary-button wire:click="cancelBooking" class="border-none btn bg-slate-300 text-slate-600">
            Abandon Reservation
        </x-mary-button>
    @elseif($booking->status === BookingStatus::CONFIRMED)
        <div class="flex flex-col items-center gap-3" id="form">
            <div class="text-xl font-semibold text-black divider divider-neutral">Reservation Confirmed</div>
            <p>Thank you for the booking! We are notifying the restaurant now.</p>
        </div>
    @endif

</x-filament-widgets::widget>

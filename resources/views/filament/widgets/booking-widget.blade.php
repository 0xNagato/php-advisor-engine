@php use App\Enums\BookingStatus; @endphp
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

        {{ $this->form }}

        @if($unavailableSchedules && $unavailableSchedules->isNotEmpty())
            <div class="bg-red-50 border border-red-400 text-red-700 p-4 rounded relative text-xs" role="alert">
                <div class="font-bold mb-2">Unavailable Times:</div>
                <div class="grid grid-cols-4 gap-x-4">
                    @foreach($unavailableSchedules as $schedule)
                        <div>{{ $schedule->formatted_start_time }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="selectedScheduleId" :disabled="!$selectedRestaurantId">
                <option value="">Select a time</option>
                @foreach ($schedules ?? [] as $schedule)
                    <option value="{{ $schedule->id }}">{{ $schedule->formatted_start_time }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>


        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="guestCount" :disabled="!$selectedScheduleId">
                <option value="">How many guests?</option>
                @for ($i = 2; $i <= 8; $i++)
                    @php
                        if ($selectedRestaurant) {
                            $price = $selectedRestaurant->getPriceForDate($selectedDate) + ($i - 2) * 50;
                        } else {
                            $price = 0;
                        }
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
        <div class="w-full -mt-4">
            <livewire:booking.invoice-small :booking="$booking"/>
        </div>


        @env('local')
            <x-filament::button tag="a" :href="$bookingUrl">
                Customer Booking Link
            </x-filament::button>
        @endenv


        <div x-data="{ tab: 'collectPayment' }" id="tabs">

            <div class="flex space-x-4">
                <div class="flex space-x-1 text-xs border-b-2 border-indigo-600 w-full">
                    <button
                        :class="{ 'bg-indigo-600 text-white': tab === 'collectPayment' }"
                        @click="tab = 'collectPayment'"
                        class="px-4 py-2 text-xs font-semibold rounded-t bg-gray-50">
                        Credit Card
                    </button>
                    <button
                        :class="{ 'bg-indigo-600 text-white': tab === 'smsPayment' }"
                        @click="tab = 'smsPayment'"
                        class="px-4 py-2 text-xs font-semibold rounded-t bg-gray-50">
                        SMS Link
                    </button>
                    <button
                        :class="{ 'bg-indigo-600 text-white': tab === 'qrCode' }"
                        @click="tab = 'qrCode'"
                        class="px-4 py-2 text-xs font-semibold rounded-t bg-gray-50">
                        QR Code
                    </button>
                </div>
            </div>

            <div x-show="tab === 'collectPayment'" class="mt-4">
                <!-- Collect Payment Tab Content -->
                <div class="text-base font-semibold mb-3 text-center">
                    Enter Reservation Details
                </div>

                <!-- @todo Refactor this to a separate component -->
                <div wire:ignore class="flex flex-col items-center gap-3" x-data="{}" x-init="() => {
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
                            const agreeCheckbox = document.querySelector('input[name=agree]');
                            if (!agreeCheckbox.checked) {
                                alert('You must agree to receive your reservation confirmation via text message.');
                                e.preventDefault();
                                return;
                            }
                            e.preventDefault();
                            $wire.$set('isLoading', true);

                            const {token, error} = await stripe.createToken(card);

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

                            <label class="w-full">
                                <input name="email" type="email"
                                       class="w-full rounded-lg border border-indigo-600 text-sm h-[40px]"
                                       placeholder="Email Address (optional)">
                            </label>

                            <div id="card-element"
                                 class="w-full rounded-lg border border-indigo-600 text-sm bg-white px-2 py-3 h-[40px]">
                                <!-- A Stripe Element will be inserted here. -->
                            </div>

                            <div class="flex items-center gap-2">
                                <label class="text-[11px] flex items-center gap-1">
                                    <x-filament::input.checkbox checked name="agree"/>
                                    <span>I agree to receive my reservation confirmation via text message.</span>
                                </label>
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
                        <p>Advise customer to check their phone for reservation payment link.</p>
                        <p>Sending message to customer now.</p>
                    </div>
                @endif
                @php
                    $message = "Your reservation at {$booking->restaurant->restaurant_name} is pending. Please click $bookingUrl to secure your booking within the next 5 minutes.";
                @endphp
                <livewire:s-m-s-input :message="$message"/>
            </div>

            <div x-show="tab === 'qrCode'" class="mt-4 flex flex-col gap-4">
                <!-- QR Code Tab Content -->
                <div class="text-base font-semibold mb-2">
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

        <x-filament::button wire:click="resetBooking" class="w-full bg-[#421fff] h-[48px]"
                            icon="gmdi-restaurant-menu">
            Create New Booking
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

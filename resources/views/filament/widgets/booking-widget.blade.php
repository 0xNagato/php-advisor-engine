<x-filament-widgets::widget class="flex flex-col gap-4">

    @if(!$booking)
        <div
            x-data="{
                showCalendar: false,
                today: new Date().toISOString().split('T')[0],
                tomorrow: new Date(new Date().setDate(new Date().getDate() + 1)).toISOString().split('T')[0]
            }"
        >

            <div class="flex space-x-4" x-bind:class="{'mb-4': showCalendar, 'mb-2': !showCalendar}">
                <label class="inline-flex items-center">
                    <input
                        checked
                        type="radio"
                        class="form-radio"
                        name="date"
                        value="today"
                        @click="showCalendar = false; $wire.selectedDate = today"
                    >
                    <span class="ml-2">Today</span>
                </label>
                <label class="inline-flex items-center">
                    <input
                        type="radio"
                        class="form-radio"
                        name="date"
                        value="tomorrow"
                        @click="showCalendar = false; $wire.selectedDate = tomorrow"
                    >
                    <span class="ml-2">Tomorrow</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" class="form-radio" name="date" value="calendar" @click="showCalendar = true">
                    <span class="ml-2">Select Date</span>
                </label>
            </div>

            <x-filament::input.wrapper
                x-show="showCalendar"
                @click="$refs.calendar.focus()"
                suffix-icon="heroicon-m-calendar"
            >
                <x-filament::input
                    type="date"
                    x-ref="calendar"
                    wire:model="selectedDate"
                />
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


        <x-filament::button
            wire:click="createBooking"
            class="w-full bg-[#421fff] h-[48px]"
            :disabled="!$guestCount"
            icon="gmdi-restaurant-menu">
            Hold Reservation
        </x-filament::button>

    @endif


    @if ($booking)
        <livewire:booking-status-widget :booking="$booking"/>

        <x-mary-button external :link="$bookingUrl" class="btn bg-[#421fff] text-white" icon="o-credit-card">
            Collect Guest's Credit Card
        </x-mary-button>

        <div class="divider divider-neutral text-xl text-black font-semibold">OR</div>

        <div class="text-base">
            Show QR code below to customer to accept secure payment.
        </div>

        <img src="{{ $qrCode }}" alt="QR Code" class="w-1/2 mx-auto shadow-lg">

        <x-mary-button wire:click="cancelBooking" class="btn bg-slate-300 border-none text-slate-600">
            Abandon Reservation
        </x-mary-button>
    @endif

</x-filament-widgets::widget>

<x-filament-widgets::widget class="flex flex-col gap-4">

    @if(!$booking)
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
            icon="gmdi-table-bar-r">
            Hold Reservation
        </x-filament::button>

    @endif


    @if ($booking)

        <x-mary-button external :link="$bookingUrl" class="btn bg-[#421fff] text-white" icon="o-credit-card">
            Collect Guest's Credit Card
        </x-mary-button>

        <div class="divider divider-neutral text-xl text-black font-semibold">OR</div>

        <div class="text-base">
            Let the guest scan the QR code to make a secure payment.
        </div>

        <img src="{{ $qrCode }}" alt="QR Code" class="w-1/2 mx-auto shadow-lg">

        <x-mary-button wire:click="cancelBooking" class="btn bg-slate-300 border-none text-slate-600">
            Abandon Reservation
        </x-mary-button>

        <livewire:booking-status-widget :booking="$booking"/>
    @endif

</x-filament-widgets::widget>

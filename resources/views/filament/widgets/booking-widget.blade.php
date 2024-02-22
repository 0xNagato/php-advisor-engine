<x-filament-widgets::widget class="flex flex-col gap-4">

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


    @if ($guestCount)
        <x-mary-button external :link="$bookingUrl" class="btn btn-primary text-white" icon="o-credit-card">
            Collect Guest's Credit Card
        </x-mary-button>

        <img src="{{ $qrCode }}" alt="QR Code" class="w-1/2 mx-auto">
    @endif

</x-filament-widgets::widget>

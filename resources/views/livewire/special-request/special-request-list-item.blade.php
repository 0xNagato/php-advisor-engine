<x-filament-widgets::widget>
    <div class="flex items-center gap-2 px-4 py-2 text-sm">
        <x-filament::avatar src="{{ $specialRequest->restaurant->logo }}" alt="Dan Harrin" />
        <div class="flex flex-col flex-grow gap-y-1">
            <div class="font-semibold">
                {{ $specialRequest->restaurant->restaurant_name }}
            </div>
            <div class="flex gap-2 text-xs">
                <div>
                    {{ $specialRequest->booking_date->format('M j, Y') }} &dash;
                    {{ $specialRequest->booking_time->format('g:i A') }}
                </div>
                <div class="font-semibold">
                    {{ moneyWithoutCents($specialRequest->minimum_spend * 100, $specialRequest->currency) }}
                </div>
            </div>
        </div>

        <div class="{{ $this->statusColors }} text-xs font-semibold">
            {{ $this->formattedStatus }}
        </div>
    </div>
</x-filament-widgets::widget>

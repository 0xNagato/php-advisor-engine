<x-filament-widgets::widget>
    <div class="flex items-center gap-2 px-4 py-2 text-sm">
        <div class="flex flex-col flex-grow gap-y-1">
            <div class="font-semibold">
                {{ $specialRequest->restaurant->restaurant_name }}
            </div>
            <div class="flex gap-2">
                <div>
                    {{ $specialRequest->booking_date->format('M j, Y') }}
                </div>
                <div>
                    {{ moneyWithoutCents($specialRequest->minimum_spend * 100, $specialRequest->currency) }}
                </div>
            </div>
        </div>

        <div class="{{ $this->statusColors }} text-xs font-semibold px-2 py-0.5 rounded">
            {{ $this->formattedStatus }}
        </div>
    </div>
</x-filament-widgets::widget>

<x-filament-widgets::widget>
    <div wire:click="viewSpecialRequest"
        class="flex items-center gap-2 px-4 py-2 text-sm cursor-pointer first:rounded-t-2xl last:rounded-b-2xl hover:bg-gray-50">
        <img src="{{ $specialRequest->restaurant->logo }}" alt="{{ $specialRequest->restaurant->restaurant_name }}"
            class="object-cover w-12 grayscale">
        <div class="flex flex-col flex-grow gap-y-1">
            <div class="font-semibold">
                {{ $specialRequest->restaurant->restaurant_name }}
                <span class="text-xs">
                    ({{ moneyWithoutCents($specialRequest->minimum_spend * 100, $specialRequest->currency) }})
                </span>
            </div>
            <div class="flex gap-2 text-xs">
                <div>
                    {{ $specialRequest->booking_date->format('M j, Y') }} &dash;
                    {{ $specialRequest->booking_time->format('g:i A') }}
                </div>
            </div>
        </div>

        <div class="{{ $this->statusColor }} text-[11px] font-semibold border rounded px-1.5">
            {{ $this->formattedStatus }}
        </div>
    </div>
</x-filament-widgets::widget>

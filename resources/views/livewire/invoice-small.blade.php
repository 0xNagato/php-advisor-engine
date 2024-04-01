<x-filament-widgets::widget>
    <div class="flex items-center w-full gap-4 p-3 bg-white bg-opacity-90 shadow rounded-xl">
        <x-heroicon-o-building-storefront class="w-10 h-10 p-2 text-white bg-orange-500 rounded-full"/>

        <div class="flex flex-col gap-1 flex-grow">
            <div class="font-semibold">{{ $booking->schedule->restaurant->restaurant_name }}</div>
            <div class="text-xs text-slate-600">
                <div>{{ $this->dayDisplay }} {{ $booking->booking_at->format('g:i a') }}</div>
                <div>{{ $booking->guest_count }} Guests</div>
            </div>
        </div>
        <div class="flex text-right flex-col gap-1">
            <div class="font-semibold">
                {{ money($booking->total_with_tax_in_cents) }}
            </div>
            <div class="text-xs text-slate-700">
                <div class="flex justify-between gap-x-2">
                    <span>Subtotal:</span> <span>{{ money($booking->total_fee) }}</span>
                </div>
                <div class="flex justify-between gap-x-2">
                    <span>Tax:</span> <span>{{ money($booking->tax_amount_in_cents) }}</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

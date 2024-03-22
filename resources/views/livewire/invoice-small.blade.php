<x-filament-widgets::widget>
    <div class="flex items-center w-full gap-4 p-3 bg-white bg-opacity-90 shadow rounded-xl">
        <x-mary-icon name="o-building-storefront" class="w-10 h-10 p-2 text-white bg-orange-500 rounded-full"/>

        <div class="flex flex-col gap-1">
            <div class="font-semibold">{{ $booking->schedule->restaurant->restaurant_name }}</div>
            <div class="text-xs text-slate-600">
                {{ $this->dayDisplay }} {{ $booking->booking_at->format('g:i a') }} ({{ $booking->guest_count }} Guests)
            </div>
        </div>
        <div class="flex-grow flex text-right flex-col gap-1">
            <div class="font-semibold">
                {{ money($booking->total_with_tax_in_cents) }}
            </div>
            <div class="text-xs text-slate-600">
                <div>
                    Subtotal: {{ money($booking->total_fee) }}
                </div>
                <div>
                    {{ $booking->tax * 100 }}% Tax: {{ money($booking->tax_amount_in_cents) }}
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

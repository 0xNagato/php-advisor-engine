<x-filament-widgets::widget>
    <div class="flex items-center w-full gap-4 p-3 bg-white shadow bg-opacity-90 rounded-xl">
        <div class="flex items-center justify-center w-16 h-12">
            @if ($booking->venue->logo)
                <img src="{{ $booking->venue->logo }}" alt="{{ $booking->venue->name }}"
                    class="object-contain max-h-[48px] max-w-[64px]">
            @else
                <span class="text-sm font-semibold text-center line-clamp-2">
                    {{ $booking->venue->name }}
                </span>
            @endif
        </div>

        <div class="flex flex-col flex-grow gap-1">
            <div class="font-semibold">{{ $booking->venue->name }}</div>
            <div class="text-xs text-slate-600">
                <div>{{ $booking->booking_at->format('l, M jS') }}</div>
                <div>{{ $booking->booking_at->format('g:i a') }}, {{ $booking->guest_count }} guests</div>
            </div>
        </div>
        @if ($booking->is_prime && $showAmount)
            <div class="flex flex-col gap-1 text-right">
                <div class="font-semibold">
                    @money($booking->total_with_tax_in_cents, $booking->currency)
                </div>
                <div class="text-xs text-slate-700">
                    @if ($booking->tax > 0)
                        <div class="flex justify-between gap-x-2">
                            <span>Subtotal:</span> <span>@money($booking->total_fee, $booking->currency)</span>
                        </div>
                        <div class="flex justify-between gap-x-2">
                            <span>{{ $region->tax_rate_term }}:</span>
                            <span>@money($booking->tax_amount_in_cents, $booking->currency)</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>

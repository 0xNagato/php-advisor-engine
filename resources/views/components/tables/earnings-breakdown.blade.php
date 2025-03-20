<div class="grid grid-cols-4 text-xs gap-y-4">
    @foreach ($earnings as $booking_id => $bookingEarnings)
        @foreach ($bookingEarnings as $earning)
            <div onclick="window.location='{{ \App\Filament\Resources\BookingResource\Pages\ViewBooking::getUrl(['record' => $earning->booking]) }}'"
                class="cursor-pointer contents hover:bg-gray-50 group">
                <div class="flex items-center">
                    <a href="{{ \App\Filament\Resources\BookingResource\Pages\ViewBooking::getUrl(['record' => $earning->booking]) }}"
                        class="text-indigo-600 truncate hover:underline">
                        {{ $earning->booking->booking_at->format('M j, Y') }}
                    </a>
                </div>
                <div class="flex items-center">
                    @if ($earning->booking->is_prime)
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-green-100 text-green-800">
                            Prime
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-gray-100 text-gray-800">
                            Non-Prime
                        </span>
                    @endif
                </div>
                <div class="flex items-center text-[10px] sm:text-xs group-hover:text-gray-900"
                    title="{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $earning->type)) }}">
                    {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $earning->type)) }}
                </div>
                <div class="flex items-center justify-end group-hover:text-gray-900">
                    {{ money($earning->amount, $currency) }}
                </div>
            </div>
        @endforeach
    @endforeach
</div>

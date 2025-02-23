@php
    use App\Filament\Resources\BookingResource\Pages\ViewBooking;
@endphp

<div class="overflow-x-auto">
    <div class="inline-block min-w-full align-middle">
        <div class="overflow-hidden border rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500">Venue</th>
                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500">Date</th>
                        <th class="px-3 py-2 text-xs font-medium text-right text-gray-500">Earned</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($bookings as $booking)
                        <tr class="cursor-pointer hover:bg-gray-50"
                            onclick="window.location='{{ ViewBooking::getUrl([$booking]) }}'">
                            <td class="px-3 py-2 text-xs text-gray-900 whitespace-nowrap">
                                {{ Str::limit($booking->schedule->venue->name, 20) }}
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 whitespace-nowrap">
                                {{ $booking->booking_at->format('M j') }}
                            </td>
                            <td class="px-3 py-2 text-xs text-right text-gray-900 whitespace-nowrap">
                                {{ money($booking->earnings->where('user_id', auth()->id())->sum('amount'), $booking->currency) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-xs text-center text-gray-500">
                                No recent bookings found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@php
    $venue = $getRecord();
    $venueGroup = $venue->venueGroup;
    $concierges = collect([]);

    if ($venueGroup) {
        $concierges = \App\Models\Concierge::where('venue_group_id', $venueGroup->id)
            ->with(['user'])
            ->get();
    }
@endphp

<div class="space-y-2">
    <h3 class="text-sm font-medium text-gray-700">Concierges</h3>

    @if ($concierges->isEmpty())
        <div class="p-2 text-sm text-center text-gray-500 rounded-lg bg-gray-50">
            <p>No concierges in this group.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Name
                        </th>
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Hotel
                        </th>
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Confirmed Bookings
                        </th>
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Earnings
                        </th>
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($concierges as $index => $concierge)
                        @php
                            // Get confirmed bookings count using the relationship that already filters for confirmed status
                            $bookingsCount = $concierge->bookings()->count();
                            $earnings = $concierge->earnings()->confirmed()->sum('amount') / 100;
                            $formattedEarnings = '$' . number_format($earnings, 2);
                        @endphp
                        <tr class="{{ $index % 2 === 1 ? 'bg-gray-50' : '' }}">
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="flex items-center space-x-1">
                                    <span class="text-sm font-medium text-gray-900">{{ $concierge->user->name }}</span>
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    {{ $concierge->hotel_name ?: 'N/A' }}
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    {{ $bookingsCount }}
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    {{ $formattedEarnings }}
                                </div>
                            </td>
                            <td class="px-2 py-2 text-sm whitespace-nowrap">
                                <a href="{{ route('filament.admin.resources.users.edit', ['record' => $concierge->user->id]) }}"
                                    class="text-primary-600 hover:text-primary-900">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

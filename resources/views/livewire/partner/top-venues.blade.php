<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="py-1">
                Top Venues ({{ $startDate->format('M j') }} - {{ $endDate->format('M j') }})
            </div>
        </x-slot>

        <div class="-m-6 flex flex-col overflow-hidden">
            @php
                $topVenues = $this->getTopVenues();
            @endphp

            @if($topVenues->isEmpty())
                <div class="py-6 text-center">
                    <p class="text-lg text-gray-500">No data available for the selected date range.</p>
                </div>
            @else
                <table class="min-w-full overflow-hidden rounded-xl divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            Venue Name
                        </th>
                        <th scope="col"
                            class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            Bookings
                        </th>
                        <th scope="col"
                            class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            Earned
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach($topVenues as $venue)
                        <tr class="cursor-pointer hover:bg-gray-50"
                            wire:click="viewVenue({{ $venue['venue_id'] }})">
                            <td class="whitespace-nowrap px-3 text-sm py-[1.13rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                {{ $venue['venue_name'] }}
                            </td>
                            <td class="whitespace-nowrap px-3 text-sm py-[1.13rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                {{ $venue['booking_count'] }}
                            </td>
                            <td class="whitespace-nowrap px-3 text-sm py-[1.13rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                ${{ number_format($venue['total_usd'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

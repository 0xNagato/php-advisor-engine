<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="py-1">
                Top Concierges ({{ $startDate->format('M j') }} - {{ $endDate->format('M j') }})
            </div>
        </x-slot>

        <div class="-m-6 flex flex-col">
            @php
                $topConcierges = $this->getTopConcierges();
            @endphp

            @if($topConcierges->isEmpty())
                <div class="py-6 text-center">
                    <p class="text-lg text-gray-500">No data available for the selected date range.</p>
                </div>
            @else
                <table class="min-w-full overflow-hidden rounded-xl divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            Concierge Name
                        </th>
                        <th scope="col"
                            class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            Number of Bookings
                        </th>
                        <th scope="col"
                            class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            Total Earned
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach($topConcierges as $concierge)
                        <tr class="cursor-pointer hover:bg-gray-50"
                            wire:click="viewConcierge({{ $concierge['concierge_id'] }})">
                            <td class="whitespace-nowrap px-3 text-sm py-[1.13rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                {{ $concierge['concierge_name'] }}
                            </td>
                            <td class="whitespace-nowrap px-3 text-sm py-[1.13rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                {{ $concierge['booking_count'] }}
                            </td>
                            <td class="whitespace-nowrap px-3 text-sm py-[1.13rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                ${{ number_format($concierge['total_usd'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

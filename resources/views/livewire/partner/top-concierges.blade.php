<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="py-1">
                Top Concierges ({{ $startDate->format('M j') }} - {{ $endDate->format('M j') }})
            </div>
        </x-slot>

        <div class="flex flex-col -m-6">
            @php
                $topConcierges = $this->getTopConcierges();
            @endphp

            @if ($topConcierges->isEmpty())
                <div class="py-6 text-center">
                    <p class="text-lg text-gray-500">No data available for the selected date range.</p>
                </div>
            @else
                <table class="min-w-full overflow-hidden divide-y divide-gray-200 rounded-b-xl">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                Concierge
                            </th>
                            <th scope="col"
                                class="hidden px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6 sm:table-cell">
                                Bookings
                            </th>
                            <th scope="col"
                                class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                Earned
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($topConcierges as $concierge)
                            <tr class="{{ auth()->user()->hasRole('super_admin') ? 'cursor-pointer hover:bg-gray-50' : '' }}"
                                @if (auth()->user()->hasRole('super_admin')) wire:click="viewConcierge({{ $concierge['concierge_id'] }})" @endif>
                                <td
                                    class="whitespace-nowrap px-3 text-xs py-[1rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    {{ $concierge['concierge_name'] }}
                                </td>
                                <td
                                    class="hidden whitespace-nowrap px-3 text-xs py-[1rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6 sm:table-cell">
                                    {{ $concierge['booking_count'] }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 text-xs py-[1rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
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

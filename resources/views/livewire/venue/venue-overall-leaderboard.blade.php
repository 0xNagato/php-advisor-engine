<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="py-1">
                    Venue Leaderboard
                </div>
                @if ($this->showRegionFilter())
                    <div>
                        <select wire:model.live="selectedRegion"
                            class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">All Regions</option>
                            @foreach ($this->getRegions() as $region)
                                <option value="{{ $region['value'] }}">{{ $region['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </x-slot>

        <div class="flex flex-col -m-6 overflow-x-auto">
            @php
                $leaderboardData = $this->getLeaderboardData();
            @endphp

            @if ($leaderboardData->isEmpty())
                <div class="py-6 text-center">
                    <p class="text-lg text-gray-500">No data available for the selected date range and region.</p>
                </div>
            @else
                <table class="min-w-full overflow-hidden divide-y divide-gray-200 rounded-b-xl">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                Rank
                            </th>
                            <th scope="col"
                                class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                Venue
                            </th>
                            <th scope="col"
                                class="hidden sm:table-cell px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                Bookings
                            </th>
                            <th scope="col"
                                class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                Earned
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($leaderboardData as $venue)
                            <tr class="{{ auth()->user()->hasActiveRole('super_admin') ? 'hover:bg-gray-50 cursor-pointer' : '' }}"
                                @if (auth()->user()->hasActiveRole('super_admin')) wire:click="viewVenue({{ $venue['venue_id'] }})" @endif>
                                <td
                                    class="whitespace-nowrap px-3 text-xs font-medium py-[1rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    {{ $venue['rank'] }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 text-xs py-[1rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    @if (auth()->user()->venue && auth()->user()->venue->id === $venue['venue_id'])
                                        Your Venue
                                    @elseif(auth()->user()->hasActiveRole('super_admin'))
                                        {{ $venue['venue_name'] }}
                                    @else
                                        {{ substr($venue['venue_name'], 0, 1) . str_repeat('*', strlen($venue['venue_name']) - 1) }}
                                    @endif
                                </td>
                                <td
                                    class="hidden sm:table-cell whitespace-nowrap px-3 text-xs py-[1rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    {{ number_format($venue['booking_count']) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-3 text-xs py-[1rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    {{ $venue['currency_symbol'] }}{{ number_format($venue['total_earned'] / 100, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

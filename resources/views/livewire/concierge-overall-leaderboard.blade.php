<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="py-1">
                Concierge Leaderboard ({{ $startDate->format('M j') }} - {{ $endDate->format('M j') }})
            </div>
        </x-slot>

        <div class="flex flex-col -m-6 overflow-x-auto">
            @php
                $leaderboardData = $this->getLeaderboardData();
            @endphp

            @if ($leaderboardData->isEmpty())
                <div class="py-6 text-center">
                    <p class="text-lg text-gray-500">No data available for the selected date range.</p>
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
                                Concierge
                            </th>
                            <th scope="col"
                                class="hidden sm:table-cell px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                Direct
                            </th>
                            <th scope="col"
                                class="hidden sm:table-cell px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                Referral
                            </th>
                            <th scope="col"
                                class="px-3 text-left text-sm font-semibold py-3.5 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                Earned
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($leaderboardData as $index => $concierge)
                            <tr
                                class="{{ auth()->user()->hasActiveRole('super_admin') ? 'hover:bg-gray-100' : 'cursor-not-allowed' }}">
                                <td>
                                    <div class="px-3 py-1 text-xs font-medium whitespace-nowrap">
                                        {{ $index + 1 }}
                                    </div>
                                </td>
                                <td>
                                    @if (auth()->user()->hasActiveRole('super_admin'))
                                        @php
                                            $nameParts = !empty($concierge['user_name'])
                                                ? explode(' ', $concierge['user_name'])
                                                : [''];
                                            $firstName = $nameParts[0] ?? '';
                                            $lastName =
                                                count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
                                        @endphp
                                        <div class="px-3 py-1 text-xs whitespace-nowrap text-gray-950">
                                            {{ $firstName }} <span class="text-gray-500">{{ $lastName }}</span>
                                        </div>
                                    @else
                                        <div class="px-3 py-1 text-xs whitespace-nowrap">
                                            {{ $concierge['user_name'] ?? 'Unknown User' }}
                                        </div>
                                    @endif
                                </td>
                                <td
                                    class="hidden sm:table-cell whitespace-nowrap px-3 text-xs py-[1rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    {{ number_format($concierge['direct_booking_count']) }}
                                </td>
                                <td
                                    class="hidden sm:table-cell whitespace-nowrap px-3 text-xs py-[1rem] text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    {{ number_format($concierge['referral_booking_count']) }}
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

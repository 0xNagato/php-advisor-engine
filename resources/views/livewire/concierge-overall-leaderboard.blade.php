<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="py-1">
                Concierge Leaderboard ({{ $startDate->format('M j') }} - {{ $endDate->format('M j') }})
            </div>
        </x-slot>

        <div class="flex flex-col -m-6">
            @php
                $leaderboardData = $this->getLeaderboardData();
            @endphp

            @if($leaderboardData->isEmpty())
                <div class="text-center py-6">
                    <p class="text-gray-500 text-lg">No data available for the selected date range.</p>
                </div>
            @else
                <table class="divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-3 py-3.5 text-left text-sm font-semibold first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            Rank
                        </th>
                        <th scope="col"
                            class="px-3 py-3.5 text-left text-sm font-semibold first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            Concierge
                        </th>
                        <th scope="col"
                            class="px-3 py-3.5 text-left text-sm font-semibold first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            Earned
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach($leaderboardData as $index => $concierge)
                        <tr class="{{ auth()->user()->hasRole('super_admin') ? 'hover:bg-gray-50 cursor-pointer' : '' }}"
                            @if(auth()->user()->hasRole('super_admin'))
                                wire:click="viewConcierge({{ $concierge['concierge_id'] }})"
                                @endif
                        >
                            <td class="px-3 py-[1.13rem] whitespace-nowrap text-sm font-medium text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-3 py-[1.13rem] whitespace-nowrap text-sm text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                @if(auth()->user()->concierge && auth()->user()->concierge->user_id === $concierge['user_id'])
                                    You
                                @elseif(auth()->user()->hasRole('super_admin'))
                                    {{ $concierge['user_name'] }}
                                @else
                                    @php
                                        $nameParts = explode(' ', $concierge['user_name']);
                                        $obfuscatedName = implode(' ', array_map(function($part) {
                                            return $part[0] . str_repeat('*', strlen($part) - 1);
                                        }, $nameParts));
                                    @endphp
                                    {{ $obfuscatedName }}
                                @endif
                            </td>
                            <td class="px-3 py-[1.13rem] whitespace-nowrap text-sm text-gray-950 first-of-type:ps-4 last-of-type:pe-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
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

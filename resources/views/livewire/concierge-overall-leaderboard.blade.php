<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="py-1">
                Concierge Leaderboard ({{ $startDate->format('M j') }} - {{ $endDate->format('M j') }})
            </div>
        </x-slot>

        <div class="flex flex-col">
            <div class="-my-8 -mx-6 sm:-mx-14 overflow-x-none">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3.5 text-left text-sm font-semibold">
                                Rank
                            </th>
                            <th scope="col"
                                class="px-6 py-3.5 text-left text-sm font-semibold">
                                Concierge Name
                            </th>
                            <th scope="col"
                                class="px-6 py-3.5 text-left text-sm font-semibold">
                                Earned
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                        @foreach($this->getLeaderboardData() as $index => $concierge)
                            <tr class="{{ auth()->user()->hasRole('super_admin') ? 'hover:bg-gray-50 cursor-pointer' : '' }}"
                                @if(auth()->user()->hasRole('super_admin'))
                                    wire:click="viewConcierge({{ $concierge['concierge_id'] }})"
                                    @endif
                            >
                                <td class="px-6 py-[1.13rem] whitespace-nowrap text-sm font-medium text-gray-950">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-6 py-[1.13rem] whitespace-nowrap text-sm text-gray-950">
                                    @if(auth()->user()->concierge && auth()->user()->concierge->user_id === $concierge['user_id'])
                                        You
                                    @elseif(auth()->user()->hasRole('super_admin'))
                                        {{ $concierge['user_name'] }}
                                    @else
                                        @php
                                            $nameParts = explode(' ', $concierge['user_name']);
                                            $obfuscatedName = implode(' ', array_map(function($part) {
                                                return substr($part, 0, 1) . str_repeat('*', strlen($part) - 1);
                                            }, $nameParts));
                                        @endphp
                                        {{ $obfuscatedName }}
                                    @endif
                                </td>
                                <td class="px-6 py-[1.13rem] whitespace-nowrap text-sm text-gray-950">
                                    ${{ number_format($concierge['total_usd'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

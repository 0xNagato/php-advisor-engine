<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form Section --}}
        <x-filament::section>
            <x-slot name="heading">
                Check CoverManager Availability
            </x-slot>

            <x-slot name="description">
                Select a venue with CoverManager integration to check available time slots for a specific date and party
                size.
            </x-slot>

            <form wire:submit="checkAvailability" class="space-y-6">
                {{ $this->form }}

                <div class="flex gap-3">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <x-filament::loading-indicator class="h-4 w-4" wire:loading wire:target="checkAvailability" />
                        <span wire:loading.remove wire:target="checkAvailability">Check Availability</span>
                        <span wire:loading wire:target="checkAvailability">Checking...</span>
                    </x-filament::button>

                    @if ($showResults)
                        <x-filament::button color="gray" wire:click="resetResults">
                            Reset Results
                        </x-filament::button>
                    @endif

                    @if ($showCalendarResults)
                        <x-filament::button color="gray" wire:click="resetResults">
                            Reset Calendar Results
                        </x-filament::button>
                    @endif
                </div>
            </form>
        </x-filament::section>

        {{-- Results Section --}}
        @if ($showResults && !empty($availabilityData))
            <x-filament::section>
                <x-slot name="heading">
                    Availability Results
                </x-slot>

                <div class="space-y-4">
                    {{-- Summary Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Venue</div>
                            <div class="text-lg font-semibold">{{ $availabilityData['venue_name'] }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</div>
                            <div class="text-lg font-semibold">{{ $availabilityData['date'] }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">API Party Size</div>
                            <div class="text-lg font-semibold">{{ $availabilityData['party_size'] }} people</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Restaurant ID</div>
                            <div class="text-lg font-semibold font-mono">{{ $availabilityData['restaurant_id'] }}</div>
                        </div>
                    </div>

                    {{-- Available Time Slots for All Party Sizes --}}
                    @if (!empty($availabilityData['all_party_sizes']))
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Available Time Slots by Party Size</h3>

                            @foreach ($availabilityData['all_party_sizes'] as $partySize => $timeSlots)
                                <div class="mb-6">
                                    <h4 class="text-md font-semibold mb-3 flex items-center">
                                        <x-heroicon-o-users class="h-5 w-5 mr-2 text-gray-600 dark:text-gray-400" />
                                        {{ $partySize }} {{ $partySize == 1 ? 'Person' : 'People' }}
                                        <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                            ({{ count($timeSlots) }} slots available)
                                        </span>
                                    </h4>

                                    @if (!empty($timeSlots))
                                        <div
                                            class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-2">
                                            @foreach ($timeSlots as $slot)
                                                <div
                                                    class="flex items-center justify-center p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                                    <div class="text-center">
                                                        <div
                                                            class="font-semibold text-green-800 dark:text-green-200 text-sm">
                                                            {{ $slot['time'] }}
                                                        </div>
                                                        @if ($slot['has_discount'])
                                                            <div class="text-xs text-green-600 dark:text-green-400">
                                                                discount
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500 dark:text-gray-400 italic">
                                            No time slots available for this party size
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div
                            class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <div class="flex items-center">
                                <x-heroicon-o-exclamation-triangle
                                    class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mr-2" />
                                <div>
                                    <div class="font-semibold text-yellow-800 dark:text-yellow-200">
                                        No availability found for any party size
                                    </div>
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                        This venue may not have any open slots for the selected date.
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Raw Data (for debugging) --}}
                    @if (config('app.debug'))
                        <x-filament::section collapsible collapsed>
                            <x-slot name="heading">
                                Raw API Response (Debug)
                            </x-slot>

                            <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg text-xs overflow-x-auto">{{ json_encode($availabilityData['raw_data'], JSON_PRETTY_PRINT) }}</pre>
                        </x-filament::section>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- Calendar Results Section --}}
        @if ($showCalendarResults && !empty($calendarData))
            <x-filament::section>
                <x-slot name="heading">
                    Calendar Availability Results
                </x-slot>

                <div class="space-y-4">
                    {{-- Summary Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Venue</div>
                            <div class="text-lg font-semibold">{{ $calendarData['venue_name'] }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Date Range</div>
                            <div class="text-lg font-semibold">
                                @if ($calendarData['date_start'] && $calendarData['date_end'])
                                    {{ \Carbon\Carbon::parse($calendarData['date_start'])->format('M j, Y') }} -
                                    {{ \Carbon\Carbon::parse($calendarData['date_end'])->format('M j, Y') }}
                                @elseif($calendarData['date_start'])
                                    From {{ \Carbon\Carbon::parse($calendarData['date_start'])->format('M j, Y') }}
                                @elseif($calendarData['date_end'])
                                    Until {{ \Carbon\Carbon::parse($calendarData['date_end'])->format('M j, Y') }}
                                @else
                                    Default Range
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Restaurant ID</div>
                            <div class="text-lg font-semibold font-mono">{{ $calendarData['restaurant_id'] }}</div>
                        </div>
                    </div>

                    {{-- Calendar Summary --}}
                    @if (!empty($calendarData['calendar_summary']))
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Daily Availability Summary</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($calendarData['calendar_summary'] as $date => $summary)
                                    <div
                                        class="p-4 border rounded-lg {{ $summary['has_availability'] ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="font-semibold text-lg">
                                                {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($date)->format('l') }}
                                            </div>
                                        </div>

                                        @if ($summary['has_availability'])
                                            <div class="space-y-2">
                                                <div class="flex items-center text-sm">
                                                    <x-heroicon-o-clock
                                                        class="h-4 w-4 mr-2 text-green-600 dark:text-green-400" />
                                                    <span class="font-medium">{{ $summary['total_slots'] }} slots
                                                        available</span>
                                                </div>

                                                @if (!empty($summary['party_sizes']))
                                                    <div class="flex items-center text-sm">
                                                        <x-heroicon-o-users
                                                            class="h-4 w-4 mr-2 text-green-600 dark:text-green-400" />
                                                        <span>{{ $summary['min_party_size'] }}-{{ $summary['max_party_size'] }}
                                                            people</span>
                                                    </div>
                                                @endif

                                                @if ($summary['first_available_time'] && $summary['last_available_time'])
                                                    <div class="flex items-center text-sm">
                                                        <x-heroicon-o-calendar
                                                            class="h-4 w-4 mr-2 text-green-600 dark:text-green-400" />
                                                        <span>{{ $summary['first_available_time'] }} -
                                                            {{ $summary['last_available_time'] }}</span>
                                                    </div>
                                                @endif

                                                {{-- Time slots preview --}}
                                                @if (!empty($summary['time_slots']))
                                                    <div class="mt-2">
                                                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                                                            Times:</div>
                                                        <div class="flex flex-wrap gap-1">
                                                            @php
                                                                $isExpanded = in_array($date, $this->expandedDates);
                                                                $timesToShow = $isExpanded
                                                                    ? $summary['time_slots']
                                                                    : array_slice($summary['time_slots'], 0, 6);
                                                                $hasMoreTimes = count($summary['time_slots']) > 6;
                                                            @endphp

                                                            @foreach ($timesToShow as $time)
                                                                <span
                                                                    class="px-2 py-1 bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200 text-xs rounded">
                                                                    {{ $time }}
                                                                </span>
                                                            @endforeach

                                                            @if ($hasMoreTimes)
                                                                <button
                                                                    wire:click="toggleDateExpansion('{{ $date }}')"
                                                                    class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors cursor-pointer">
                                                                    @if ($isExpanded)
                                                                        Show less
                                                                    @else
                                                                        +{{ count($summary['time_slots']) - 6 }} more
                                                                    @endif
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                <x-heroicon-o-x-circle class="h-4 w-4 mr-2" />
                                                <span>No availability</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div
                            class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <div class="flex items-center">
                                <x-heroicon-o-exclamation-triangle
                                    class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mr-2" />
                                <div>
                                    <div class="font-semibold text-yellow-800 dark:text-yellow-200">
                                        No calendar data available
                                    </div>
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                        The calendar response did not contain any availability data.
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Raw Calendar Data (for debugging) --}}
                    @if (config('app.debug'))
                        <x-filament::section collapsible collapsed>
                            <x-slot name="heading">
                                Raw Calendar API Response (Debug)
                            </x-slot>

                            <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg text-xs overflow-x-auto">{{ json_encode($calendarData['raw_data'], JSON_PRETTY_PRINT) }}</pre>
                        </x-filament::section>
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

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
    </div>
</x-filament-panels::page>

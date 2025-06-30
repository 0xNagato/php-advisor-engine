<x-filament-panels::page>

    {{-- REMOVE Static Venue Name --}}
    {{-- <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Ibiza Hike Station</h2> --}}

    {{-- Step 1: Initial Selection Form & Availability Display --}}
    <form wire:submit.prevent="updateAvailabilityDisplay">
        {{ $this->form }}
    </form>

    {{-- Availability Display Area --}}
    @if ($showAvailability)
        {{-- Adjust gap/margin for spacing --}}
        <div class="flex flex-col gap-2 -mt-4">
            {{-- Availability for Selected Day --}}
            @if (!empty($selectedDaySlots))
                <div class="flex flex-wrap gap-2 mb-2">
                    @foreach ($selectedDaySlots as $slotData)
                        @php
                            $isDisabled = !$slotData['is_available'];
                            $baseClasses =
                                'flex flex-col gap-1 items-center px-3 py-3 text-sm font-semibold leading-none rounded-xl relative';
                            $activeClasses = 'bg-green-600 text-white cursor-pointer hover:bg-green-500';
                            $disabledClasses =
                                'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 border-none cursor-not-allowed';
                        @endphp
                        <div @if (!$isDisabled) wire:click="selectSlot('{{ $slotData['slot_key'] }}', '{{ $slotData['date_for_click'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="selectSlot('{{ $slotData['slot_key'] }}', '{{ $slotData['date_for_click'] }}')" @endif
                            class="{{ $baseClasses }} {{ $isDisabled ? $disabledClasses : $activeClasses }} flex-grow"
                            style="min-width: 120px;" role="button" tabindex="{{ $isDisabled ? -1 : 0 }}"
                            aria-disabled="{{ $isDisabled ? 'true' : 'false' }}">
                            <div wire:loading
                                wire:target="selectSlot('{{ $slotData['slot_key'] }}', '{{ $slotData['date_for_click'] }}')"
                                class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/50 dark:bg-gray-900/50">
                                <x-filament::loading-indicator class="w-6 h-6" />
                            </div>
                            <div class="text-lg text-center">{{ $slotData['time'] }}</div>
                            <div class="text-sm">
                                @if ($isDisabled)
                                    Unavailable
                                @elseif ($slotData['price'] !== null)
                                    {{ $this->currency === 'EUR' ? '€' : '$' }}{{ number_format($slotData['price'] / 100, 2) }}
                                @else
                                    Price Error
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Next 3 Days Availability Display --}}
            @if (!empty($nextDaysAvailability))
                {{-- Keep ResHub heading classes --}}
                <div class="mt-0 text-sm font-bold text-center uppercase">
                    Next 3 Days Availability
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach ($nextDaysAvailability as $dayData)
                        <div
                            class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="mb-2 text-sm font-medium text-center text-gray-700 dark:text-gray-300">
                                {{ $dayData['date_formatted'] }}</div>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($dayData['slots'] as $slotData)
                                    @php
                                        $isNextDayDisabled = !$slotData['is_available'];
                                        $baseClasses =
                                            'flex flex-col gap-1 items-center px-2 py-2 text-xs font-semibold leading-none rounded-lg relative';
                                        $nextDayActiveClasses =
                                            'bg-green-600 text-white cursor-pointer hover:bg-green-500';
                                        $nextDayDisabledClasses =
                                            'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 border-none cursor-not-allowed';
                                    @endphp
                                    <div @if (!$isNextDayDisabled) wire:click="selectSlot('{{ $slotData['slot_key'] }}', '{{ $dayData['date_value'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="selectSlot('{{ $slotData['slot_key'] }}', '{{ $dayData['date_value'] }}')" @endif
                                        class="{{ $baseClasses }} {{ $isNextDayDisabled ? $nextDayDisabledClasses : $nextDayActiveClasses }}"
                                        role="button" tabindex="{{ $isNextDayDisabled ? -1 : 0 }}"
                                        aria-disabled="{{ $isNextDayDisabled ? 'true' : 'false' }}">
                                        <div wire:loading
                                            wire:target="selectSlot('{{ $slotData['slot_key'] }}', '{{ $dayData['date_value'] }}')"
                                            class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/50 dark:bg-gray-900/50">
                                            <x-filament::loading-indicator class="w-5 h-5" />
                                        </div>
                                        <div class="font-semibold">{{ $slotData['time_formatted'] }}</div>
                                        <div class="text-xs">
                                            @if ($isNextDayDisabled)
                                                Unavailable
                                            @elseif ($slotData['price'] !== null)
                                                {{ $this->currency === 'EUR' ? '€' : '$' }}{{ number_format($slotData['price'] / 100, 2) }}
                                            @else
                                                Price Error
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

</x-filament-panels::page>

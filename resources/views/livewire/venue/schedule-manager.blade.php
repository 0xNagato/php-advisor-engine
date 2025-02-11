@php
    use Carbon\Carbon;
@endphp
<div class="space-y-2">
    <style>
        /* Override Filament's default section padding on mobile */
        .fi-section-content {
            padding: 0.5rem !important;
            /* p-2 */
        }

        /* Restore default padding on larger screens */
        @media (min-width: 640px) {
            .fi-section-content {
                padding: 1.5rem !important;
                /* p-6 */
            }
        }
    </style>

    <x-filament::tabs>
        <x-filament::tabs.item wire:click="$set('activeView', 'template')" :active="$activeView === 'template'">
            Weekly Template
        </x-filament::tabs.item>
        <x-filament::tabs.item wire:click="$set('activeView', 'calendar')" :active="$activeView === 'calendar'">
            Calendar Overrides
        </x-filament::tabs.item>
    </x-filament::tabs>

    @if ($activeView === 'template')
        <x-filament::section class="p-2">
            <!-- Day selector -->
            <div class="mb-2">
                <div class="grid grid-cols-7 gap-1">
                    @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                        <button wire:click="$set('selectedDay', '{{ $day }}')" @class([
                            'px-1 py-1 text-xs rounded-lg',
                            'bg-primary-500 text-white' => $selectedDay === $day,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200' => $selectedDay !== $day,
                        ])>
                            {{ substr(ucfirst($day), 0, 3) }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($venue->open_days[$selectedDay] === 'closed')
                <div class="flex items-center justify-center p-4 text-sm text-gray-500">
                    Venue is closed on {{ ucfirst($selectedDay) }}s
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="w-16 px-1 py-1 text-xs font-semibold text-left text-gray-900">
                                    Time
                                </th>
                                @foreach ($venue->party_sizes as $size => $label)
                                    @unless ($size === 'Special Request')
                                        <th class="w-16 px-1 py-1 text-xs font-semibold text-center text-gray-900">
                                            {{ $size }}
                                        </th>
                                    @endunless
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($timeSlots as $slot)
                                <tr>
                                    <td class="px-1 py-1 text-xs text-gray-500">
                                        <button wire:click="openBulkTemplateEditModal('{{ $slot['time'] }}')"
                                            class="w-full text-left underline transition-colors hover:text-primary-600"
                                            title="Edit all party sizes for this time slot">
                                            {{ Carbon::parse($slot['time'])->format('g:i A') }}
                                        </button>
                                    </td>
                                    @foreach ($venue->party_sizes as $size => $label)
                                        @unless ($size === 'Special Request')
                                            <td class="px-1 py-1">
                                                <button type="button"
                                                    wire:click="openEditModal('template', '{{ $slot['time'] }}', '{{ $size }}')"
                                                    @class([
                                                        'w-full px-2 py-1 text-xs font-medium rounded',
                                                        'bg-green-50 hover:bg-green-100 text-green-700' =>
                                                            $schedules[$selectedDay][$slot['time']][$size]['is_prime'] &&
                                                            $schedules[$selectedDay][$slot['time']][$size]['is_available'],
                                                        'bg-blue-50 hover:bg-blue-100 text-blue-700' =>
                                                            !$schedules[$selectedDay][$slot['time']][$size]['is_prime'] &&
                                                            $schedules[$selectedDay][$slot['time']][$size]['is_available'],
                                                        'bg-red-50 hover:bg-red-100 text-red-700' => !$schedules[$selectedDay][
                                                            $slot['time']
                                                        ][$size]['is_available'],
                                                    ])>
                                                    {{ $schedules[$selectedDay][$slot['time']][$size]['is_available']
                                                        ? $schedules[$selectedDay][$slot['time']][$size]['available_tables']
                                                        : '--' }}
                                                </button>
                                            </td>
                                        @endunless
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-slot:footer>
                    <div class="flex justify-end">
                        <x-filament::button wire:click="saveTemplate" size="sm">
                            Save Template
                        </x-filament::button>
                    </div>
                </x-slot:footer>
            @endif
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="space-y-4">
                <!-- Calendar Component -->
                <div>
                    <livewire:components.calendar :selected-date="$selectedDate" :today-date="$todayDate" :timezone="$venue->timezone ?? config('app.timezone')"
                        :dates-with-overrides="$this->getDatesWithOverrides()" wire:key="calendar-{{ $selectedDate }}" />
                </div>

                <!-- Schedule Grid -->
                <div>
                    @if ($selectedDate)
                        <div class="overflow-x-auto">
                            <h3 class="pl-2 mb-4">
                                <div class="sm:flex sm:items-center sm:justify-between">
                                    <div class="text-center sm:text-left">
                                        <div class="text-sm font-semibold">
                                            Schedule for {{ Carbon::parse($selectedDate)->format('l, F j, Y') }}
                                        </div>
                                        @if ($venue->open_days[strtolower(Carbon::parse($selectedDate)->format('l'))] === 'closed')
                                            <div class="text-sm text-gray-500">(Venue Closed)</div>
                                        @endif
                                        @if ($holidayInfo = $this->getHolidayInfo($selectedDate))
                                            <span class="text-lg">{{ $holidayInfo['emoji'] }}</span>
                                            <div class="text-xs text-gray-500">({{ $holidayInfo['name'] }})</div>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 mt-2 sm:mt-0 sm:flex sm:gap-3">
                                        <x-filament::button wire:click="makeDayPrime" color="success"
                                            class="justify-center">
                                            Make Day Prime
                                        </x-filament::button>

                                        <x-filament::button wire:click="closeDay" color="danger" class="justify-center">
                                            Close Day
                                        </x-filament::button>
                                    </div>
                                </div>
                            </h3>

                            @if ($venue->open_days[strtolower(Carbon::parse($selectedDate)->format('l'))] === 'closed')
                                <div class="p-4 text-center text-gray-500">
                                    This venue is closed on {{ Carbon::parse($selectedDate)->format('l') }}s
                                </div>
                            @else
                                <table class="w-full">
                                    <thead>
                                        <tr>
                                            <th class="w-20 px-1 py-1 text-xs font-semibold text-left text-gray-900">
                                                Time
                                            </th>
                                            @foreach ($venue->party_sizes as $size => $label)
                                                @unless ($size === 'Special Request')
                                                    <th
                                                        class="px-1 py-1 text-xs font-semibold text-center text-gray-900 w-14">
                                                        {{ $size }}
                                                    </th>
                                                @endunless
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($timeSlots as $slot)
                                            @if (isset($calendarSchedules[$slot['time']]))
                                                <tr>
                                                    <td class="px-1 py-1 text-xs text-gray-500">
                                                        <button
                                                            wire:click="openBulkEditModal('{{ Carbon::parse($selectedDate)->format('l') }}', '{{ $slot['time'] }}')"
                                                            class="w-full text-left underline transition-colors hover:text-primary-600"
                                                            title="Edit all party sizes for this time slot">
                                                            {{ $slot['formatted_time'] }}
                                                        </button>
                                                    </td>
                                                    @foreach ($venue->party_sizes as $size => $label)
                                                        @unless ($size === 'Special Request')
                                                            <td class="px-1 py-1">
                                                                <button type="button"
                                                                    wire:click="openEditModal('{{ $selectedDate }}', '{{ $slot['time'] }}', '{{ $size }}')"
                                                                    @class([
                                                                        'w-full px-2 py-1 text-xs font-medium rounded',
                                                                        'bg-green-50 hover:bg-green-100 text-green-700' =>
                                                                            $calendarSchedules[$slot['time']][$size]['is_prime'] &&
                                                                            $calendarSchedules[$slot['time']][$size]['is_available'],
                                                                        'bg-blue-50 hover:bg-blue-100 text-blue-700' =>
                                                                            !$calendarSchedules[$slot['time']][$size]['is_prime'] &&
                                                                            $calendarSchedules[$slot['time']][$size]['is_available'],
                                                                        'bg-red-50 hover:bg-red-100 text-red-700' => !$calendarSchedules[
                                                                            $slot['time']
                                                                        ][$size]['is_available'],
                                                                        'ring-2 ring-indigo-500 ring-opacity-50' =>
                                                                            $calendarSchedules[$slot['time']][$size]['has_override'],
                                                                    ])>
                                                                    {{ $calendarSchedules[$slot['time']][$size]['is_available']
                                                                        ? $calendarSchedules[$slot['time']][$size]['available_tables']
                                                                        : '--' }}
                                                                </button>
                                                            </td>
                                                        @endunless
                                                    @endforeach
                                                </tr>
                                            @else
                                                <tr>
                                                    <td class="px-1 py-1 text-red-500">Missing schedule for
                                                        {{ $slot['time'] }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @else
                        <div class="flex items-center justify-center h-full text-gray-500">
                            Select a date to view or modify schedules
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif

    <x-filament::modal id="edit-slot" width="sm">
        <x-slot name="header">
            <div class="flex items-center justify-between w-full">
                <span class="text-sm text-gray-600">
                    @if ($activeView === 'calendar' && isset($editingSlot['date']))
                        {{ $this->getFormattedDate($editingSlot['date']) }} at {{ $this->getFormattedTime() }}
                    @else
                        {{ ucfirst($editingSlot['day']) }} at {{ $this->getFormattedTime() }}
                    @endif
                </span>
            </div>
        </x-slot>

        <div class="space-y-4" x-data="{
            get isAvailable() {
                return $wire.editingSlot?.is_available ?? false
            },
            get isPrime() {
                return $wire.editingSlot?.is_prime ?? false
            }
        }">
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer bg-gray-50">
                    <x-filament::input.checkbox wire:model="editingSlot.is_available" class="w-4 h-4" />
                    <span class="text-sm font-medium">Available</span>
                </label>

                <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer bg-gray-50">
                    <x-filament::input.checkbox wire:model="editingSlot.is_prime" class="w-4 h-4" />
                    <span class="text-sm font-medium">Prime Time</span>
                </label>
            </div>

            <div x-show="!isPrime">
                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-2">
                        <span class="text-sm font-medium">Available Tables</span>
                        <input type="number" wire:model="editingSlot.available_tables"
                            x-bind:disabled="!$wire.editingSlot.is_available" min="0" max="30"
                            class="block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed" />
                    </div>

                    <div class="space-y-2">
                        <span class="text-sm font-medium">Incentive Per Guest</span>
                        <div class="relative">
                            <span class="absolute inset-y-0 flex items-center text-gray-500 left-3">$</span>
                            <input type="number" wire:model="editingSlot.price_per_head" min="0"
                                class="block w-full text-sm border-gray-300 rounded-lg shadow-sm pl-7 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                        </div>
                    </div>
                </div>
            </div>
            <div x-show="isPrime">
                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-2">
                        <span class="text-sm font-medium">Available Tables</span>
                        <input type="number" wire:model="editingSlot.available_tables"
                            x-bind:disabled="!$wire.editingSlot.is_available" min="0" max="30"
                            class="block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed" />
                    </div>

                    <div class="space-y-2">
                        <span class="text-sm font-medium">Min. Spend Per Guest</span>
                        <div class="relative">
                            <span class="absolute inset-y-0 flex items-center text-gray-500 left-3">$</span>
                            <input type="number" wire:model="editingSlot.minimum_spend_per_guest" min="0"
                                class="block w-full text-sm border-gray-300 rounded-lg shadow-sm pl-7 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-filament::button color="gray" wire:click="closeEditModal" size="sm">
                    Cancel
                </x-filament::button>
                <x-filament::button wire:click="saveEditingSlot" size="sm">
                    Save
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</div>

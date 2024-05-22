<!-- resources/views/filament/pages/restaurant/prime-calendar.blade.php -->

<x-filament::page>
    <form wire:submit.prevent="save">
        <div class="relative -mx-6 overflow-x-auto sm:mx-0">
            <table class="min-w-full table-fixed border border-gray-300 bg-white">
                <thead>
                <tr>
                    <th class="whitespace-nowrap border border-gray-300 px-2 py-1 text-left text-xs uppercase leading-4 tracking-wider text-gray-600">
                        Time Slot
                    </th>
                    @foreach ($upcomingDates as $date)
                        <th class="whitespace-nowrap border border-gray-300 px-2 py-1 text-left text-xs uppercase leading-4 tracking-wider text-gray-600">
                            {{ $date->format('D, M j') }}
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($timeSlots[array_key_first($timeSlots)] as $index => $slot)
                    <tr class="{{ $index % 2 === 0 ? 'bg-gray-50' : 'bg-white' }} group">
                        <td class="whitespace-nowrap border border-gray-300 px-2 py-1 text-xs font-semibold group-hover:bg-gray-100">
                            {{ Carbon\Carbon::createFromFormat('H:i:s', $slot['start'])->format('g:ia') }}
                        </td>
                        @foreach ($upcomingDates as $date)
                            <td class="whitespace-nowrap border border-gray-300 px-2 py-1 text-center group-hover:bg-gray-100">
                                @isset($timeSlots[$date->format('Y-m-d')][$index])
                                    <input type="checkbox"
                                           wire:model="selectedTimeSlots.{{ $date->format('Y-m-d') }}.{{ $index }}"
                                           class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-indigo-600 focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-indigo-600"
                                           @if(!$slot['is_checked']) checked @endif
                                    />
                                @else
                                    N/A
                                @endisset
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <x-filament::button type="submit" size="lg" class="w-full mt-4">
            Save Reservation Hours
        </x-filament::button>
    </form>
</x-filament::page>

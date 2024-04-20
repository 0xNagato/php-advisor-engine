<!--suppress ALL -->
@php
    use Carbon\Carbon;
    $firstDate = array_key_first($schedules);
@endphp
<div x-data="{
        activeAccordion: '{{ $firstDate }}',
        setActiveAccordion(id) {
            this.activeAccordion = (this.activeAccordion === id) ? '' : id
        }
    }"
     class="relative w-full mx-auto overflow-hidden text-sm font-normal bg-white border border-gray-200 divide-y divide-gray-200 rounded-lg shadow">
    @forelse ($schedules as $date => $times)
        @php $formattedDate = Carbon::parse($date)->format('l'); @endphp
        <div x-data="{ id: '{{ $date }}' }" class=" group">
            <button @click="setActiveAccordion(id)"
                    class="flex items-center justify-between w-full p-4 text-left select-none font-semibold"
            >
                <span>{{ $formattedDate }}</span>
                <svg class="w-4 h-4 duration-200 ease-out" :class="{ 'rotate-180': activeAccordion === id }"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div x-show="activeAccordion === id" x-collapse x-cloak>
                @if(empty($times))
                    <div class="text-center text-gray-500">No available time slots for this day.</div>
                @else
                    @php
                        $firstTime = array_key_first($times);
                        $partySizes = array_keys($times[$firstTime]);
                    @endphp
                    <table class="w-full text-xs table-fixed">
                        <thead>
                        <tr>
                            <th class="text-left py-2 pl-4"></th>
                            <th class="text-center">Prime</th>
                            @foreach ($partySizes as $key => $partySize)
                                @if($partySize === 'prime_time')
                                    @continue
                                @endif
                                <th class="text-center text-base pr-3">
                                    <div
                                        class="border-2 rounded-full p-0.5 m-2 h-7 w-7 flex justify-center items-center">
                                        {{ $partySize }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($times as $time => $partySizes)
                            <tr class="{{ $loop->iteration % 2 === 0 ? 'bg-gray-50' : 'bg-white' }} border-t border-gray-200">
                                <td class="font-semibold pl-4">{{ Carbon::createFromFormat('H:i:s', $time)->format('g:ia') }}</td>
                                <td class="text-center">
                                    <x-filament::input.checkbox
                                        wire:model="schedules.{{ $date }}.{{ $time }}.prime_time"/>
                                </td>
                                @foreach ($partySizes as $partySize => $availableTables)
                                    @if($partySize === 'prime_time')
                                        @continue
                                    @endif
                                    <td class="border-gray-200 py-2 text-center">
                                        <input type="number"
                                               name="schedules[{{ $date }}][{{ $time }}][{{ $partySize }}]"
                                               value="{{ $availableTables }}"
                                               wire:model="schedules.{{ $date }}.{{ $time }}.{{ $partySize }}"
                                               min="0" max="30"
                                            @class([
                                                'w-3/4 bg-gray-50 border leading-none border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block px-0.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500 text-center',
                                                'border-red-500 bg-red-100' => $errors->has('schedules.'.$date.'.'.$time.'.'.$partySize),
                                            ])
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="p-4">
                        <x-filament::button wire:click="saveTableAvailability" class="w-full">
                            Save Table Availability
                        </x-filament::button>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="p-4 text-center text-gray-500">No schedules available</div>
    @endforelse
</div>

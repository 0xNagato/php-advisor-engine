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
        <div x-data="{ id: '{{ $date }}' }" class="cursor-pointer group">
            <button @click="setActiveAccordion(id)"
                    class="flex items-center justify-between w-full p-4 text-left select-none font-semibold"
            >
                <span>{{ $formattedDate }}</span>
                <svg class="w-4 h-4 duration-200 ease-out" :class="{ 'rotate-180': activeAccordion===id }"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div x-show="activeAccordion===id" x-collapse x-cloak>
                <div class="p-4 pt-0 grid grid-cols-2 gap-3 text-xs">
                    @if(empty($times))
                        <div class="col-span-2 text-center text-gray-500">No available time slots for this day.</div>
                    @else
                        @foreach ($times as $time => $value)
                            <div class="flex items-center gap-2">
                                <div class="w-1/2">{{ Carbon::createFromFormat('H:i:s', $time)->format('g:ia') }}</div>
                                <label class="w-1/2">
                                    <input type="number" name="schedule" value="10"
                                           wire:model.live.debounce.500ms="schedules.{{ $date }}.{{ $time }}"

                                           class="w-3/4 bg-gray-50 border leading-none border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block px-2 p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500 text-center"
                                    >
                                </label>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="p-4 text-center text-gray-500">No schedules available</div>
    @endforelse
</div>

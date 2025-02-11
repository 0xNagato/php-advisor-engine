<div class="w-full">
    <div class="flex items-center justify-between">
        <button type="button" class="p-2 text-gray-400 hover:text-gray-500" wire:click="previousMonth">
            <span class="sr-only">Previous month</span>
            <x-heroicon-s-chevron-left class="w-5 h-5" />
        </button>

        <h2 class="text-sm font-semibold text-gray-900">
            {{ $currentMonthCarbon->format('F Y') }}
        </h2>

        <button type="button" class="p-2 text-gray-400 hover:text-gray-500" wire:click="nextMonth">
            <span class="sr-only">Next month</span>
            <x-heroicon-s-chevron-right class="w-5 h-5" />
        </button>
    </div>

    <div class="grid grid-cols-7 gap-px">
        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayLabel)
            <div class="px-2 py-2 text-sm font-semibold text-center text-gray-900">
                {{ $dayLabel }}
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-px mt-2 text-sm bg-gray-200">
        @foreach ($weeks as $week)
            @foreach ($week as $date)
                <button type="button" wire:click="selectDate('{{ $date->format('Y-m-d') }}')"
                    @class([
                        'p-2 bg-white hover:bg-gray-50 focus:z-10 relative',
                        'bg-indigo-50 font-semibold text-indigo-600' =>
                            $selectedDate === $date->format('Y-m-d'),
                        'text-gray-400' => !$date->isSameMonth($currentMonthCarbon),
                        'font-semibold' => $date->isToday(),
                    ])>
                    {{ $date->format('j') }}
                    @if (in_array($date->format('Y-m-d'), $datesWithOverrides))
                        <div
                            class="absolute w-1 h-1 transform -translate-x-1/2 bg-green-500 rounded-full bottom-1 left-1/2">
                        </div>
                    @endif
                </button>
            @endforeach
        @endforeach
    </div>
</div>

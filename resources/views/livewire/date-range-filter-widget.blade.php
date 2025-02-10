<x-filament-widgets::widget>
    <div x-data="{
        activeRange: @entangle('range')
    }" class="flex space-x-2">
        @foreach (['today', 'past_week', 'month', 'past_30_days', 'year'] as $rangeOption)
            @if (isPrimaApp() && isAndroid())
                <a href="?range={{ $rangeOption }}&filters[startDate]={{ $ranges[$rangeOption]['start'] }}&filters[endDate]={{ $ranges[$rangeOption]['end'] }}"
                    :class="{
                        'bg-primary-600 text-white': activeRange === '{{ $rangeOption }}',
                        'bg-gray-200 text-gray-700 hover:bg-primary-600 hover:text-white': activeRange !== '{{ $rangeOption }}'
                    }"
                    class="px-2 py-1 text-xs transition duration-150 ease-in-out rounded-full">
                    {{ Str::title(str_replace('_', ' ', $rangeOption)) }}
                </a>
            @else
                <button wire:click="setDateRange('{{ $rangeOption }}')" x-on:click="activeRange = '{{ $rangeOption }}'"
                    :class="{
                        'bg-primary-600 text-white': activeRange === '{{ $rangeOption }}',
                        'bg-gray-200 text-gray-700 hover:bg-primary-600 hover:text-white': activeRange !== '{{ $rangeOption }}'
                    }"
                    class="px-2 py-1 text-xs transition duration-150 ease-in-out rounded-full">
                    {{ Str::title(str_replace('_', ' ', $rangeOption)) }}
                </button>
            @endif
        @endforeach
    </div>
</x-filament-widgets::widget>

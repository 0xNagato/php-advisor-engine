<x-filament-widgets::widget>
    <div x-data="{
        activeRange: @entangle('range')
    }" class="flex space-x-2">
        @foreach (['today', 'past_week', 'month', 'past_30_days', 'year'] as $rangeOption)
            <a href="?range={{ $rangeOption }}&filters[startDate]={{ $ranges[$rangeOption]['start'] }}&filters[endDate]={{ $ranges[$rangeOption]['end'] }}"
                @unless (str_contains(request()->userAgent(), 'Safari') ||
                        str_contains(request()->userAgent(), 'iPhone') ||
                        str_contains(request()->userAgent(), 'iPad'))
                    wire:navigate
                @endunless
                :class="{
                    'bg-primary-600 text-white': activeRange === '{{ $rangeOption }}',
                    'bg-gray-200 text-gray-700 hover:bg-primary-600 hover:text-white': activeRange !== '{{ $rangeOption }}'
                }"
                class="px-2 py-1 text-xs transition duration-150 ease-in-out rounded-full">
                {{ Str::title(str_replace('_', ' ', $rangeOption)) }}
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>

<x-filament-widgets::widget>
    <div x-data="{
        activeRange: @entangle('range')
    }" class="flex space-x-2">
        @foreach (['past_30_days', 'past_week', 'month', 'quarter', 'year'] as $rangeOption)
            <button wire:click="setDateRange('{{ $rangeOption }}')" x-on:click="activeRange = '{{ $rangeOption }}'"
                :class="{
                    'bg-primary-600 text-white': activeRange === '{{ $rangeOption }}',
                    'bg-gray-200 text-gray-700 hover:bg-primary-600 hover:text-white': activeRange !== '{{ $rangeOption }}'
                }"
                class="px-2 py-1 text-xs rounded-full transition duration-150 ease-in-out">
                {{ Str::title(str_replace('_', ' ', $rangeOption)) }}
            </button>
        @endforeach
    </div>
</x-filament-widgets::widget>

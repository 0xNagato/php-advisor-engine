<x-filament-widgets::widget>
    <div class="flex space-x-2">
        @foreach (['last_30_days', 'week', 'month', 'quarter', 'year'] as $rangeOption)
            <button wire:click="setDateRange('{{ $rangeOption }}')"
                class="px-2 py-1 text-xs rounded-full {{ $range === $rangeOption ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                {{ Str::title(str_replace('_', ' ', $rangeOption)) }}
            </button>
        @endforeach
    </div>
</x-filament-widgets::widget>

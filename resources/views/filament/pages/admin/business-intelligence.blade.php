<x-filament-panels::page>
    @if ($isLoading)
        <div class="flex items-center justify-center h-32">
            <x-filament::loading-indicator class="h-8 w-8" />
        </div>
    @else
        <div class="space-y-6">
            {{ $this->table }}
        </div>
    @endif
</x-filament-panels::page>

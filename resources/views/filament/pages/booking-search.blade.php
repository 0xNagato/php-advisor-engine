<x-filament-panels::page>
    <div class="space-y-6">
        <div class="relative">
            <div class="absolute right-2 top-2">
                <x-filament::button wire:click="clearFilters" icon="heroicon-m-x-mark" size="xs">
                    Clear Filters
                </x-filament::button>
            </div>
            {{ $this->form }}
        </div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>

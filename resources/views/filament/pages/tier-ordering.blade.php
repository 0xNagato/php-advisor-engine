<x-filament-panels::page>
    <div class="space-y-4">
        <div class="text-sm text-gray-600">
            Use the filters to select a region and tier, then drag rows to set the ordering. Changes save automatically.
        </div>
        <div>
            {{ $this->form }}
        </div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    <livewire:concierge.vip-codes-table key="{{ now() }}" :table-filters="$filters"/>

    {{ $this->form }}
</x-filament-panels::page>

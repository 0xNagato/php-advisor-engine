<x-filament-panels::page>
    {{ $this->form }}

    <livewire:concierge.vip-codes-table key="{{ now() }}" :table-filters="$filters" />

</x-filament-panels::page>

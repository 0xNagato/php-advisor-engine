<x-filament-panels::page>
    @mobileapp
    <div class="-mt-4 -mb-4">
        <x-filament-actions::actions :actions="$this->getHeaderActions()"/>
    </div>
    @endmobileapp
    <livewire:special-request.special-request-list :concierge-id="auth()->user()->concierge->id"/>
</x-filament-panels::page>

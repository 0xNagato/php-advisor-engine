<x-filament-panels::page>
    @mobileapp
    <div class="-mb-4 w-full">
        <x-filament-actions::actions :actions="$this->getHeaderActions()"/>
    </div>
    @endmobileapp
    <livewire:special-request.special-request-list :concierge-id="auth()->user()->concierge->id"/>
</x-filament-panels::page>

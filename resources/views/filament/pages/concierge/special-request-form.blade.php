<x-filament-panels::page>
    @mobileapp
    {{ $this->getHeaderActions()[0] }}
    @endmobileapp
    <livewire:special-request.special-request-list :concierge-id="auth()->user()->concierge->id"/>
</x-filament-panels::page>

<x-filament-panels::page>
    <livewire:special-request.special-request-list :concierge-id="auth()->user()->hasActiveRole('concierge') ? auth()->user()->concierge?->id : null" />
</x-filament-panels::page>

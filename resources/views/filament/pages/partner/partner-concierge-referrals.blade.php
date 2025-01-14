<x-filament-panels::page>
    <x-referral-link-card type="partner" />

    {{ $this->tabbedForm }}

    <livewire:partner.concierge-referrals-table />

    <div class="h-0">
        <x-filament-actions::modals />
    </div>
</x-filament-panels::page>

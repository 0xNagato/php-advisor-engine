<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            User details
        </x-slot>

        <form wire:submit="sendInviteViaEmail">
            {{ $this->form }}
            <x-filament::button type="submit" class="w-full mt-4">
                Send Invitation
            </x-filament::button>
        </form>
    </x-filament::section>

    <livewire:referrals-table/>

    <div class="h-0">
        <x-filament-actions::modals/>
    </div>
</x-filament-panels::page>

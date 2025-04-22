<x-filament-panels::page>

    {{-- Add the Referral Link Card for Venue Managers --}}
    <div class="mb-6">
        <x-referral-link-card type="venue_manager" />
    </div>

    {{-- Original Form Structure --}}
    <form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Invite Concierge
        </x-filament::button>
    </form>

    {{-- Original Table Structure --}}
    <div class="mt-8">
        {{ $this->table }}
    </div>

</x-filament-panels::page>

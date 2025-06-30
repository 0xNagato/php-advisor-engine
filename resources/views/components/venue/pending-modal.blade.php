@props(['venue'])

<x-filament::modal id="pending-venue-{{ $venue->id }}" width="md">
    <x-slot name="heading">
        {{ $venue->name }} Coming Soon
    </x-slot>

    <p class="mt-2">
        Our team is currently working with {{ $venue->name }} to complete onboarding.
        We anticipate {{ $venue->name }} to be available on PRIMA soon.
    </p>

    <p class="mt-4">
        If you require an urgent reservation to this venue, please write to us at
        <a href="mailto:prima@primavip.co" class="text-primary-600 hover:text-primary-500">prima@primavip.co</a>
        and our team will assist.
    </p>

    <x-slot name="footerActions">
        <x-filament::button x-on:click="$dispatch('close-modal', { id: 'pending-venue-{{ $venue->id }}' })">
            Close
        </x-filament::button>
    </x-slot>
</x-filament::modal>

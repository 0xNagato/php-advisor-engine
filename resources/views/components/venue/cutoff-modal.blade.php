@props(['venue'])

<x-filament::modal id="cutoff-venue-{{ $venue->id }}" width="md">
    <x-slot name="heading">
        {{ $venue->name }} - Booking Cutoff Time
    </x-slot>

    <p class="mt-2">
        Online bookings for {{ $venue->name }} are available until
        <strong>{{ $venue->cutoff_time->format('g:i A') }}</strong> daily.
    </p>

    <p class="mt-4">
        For last-minute reservations, please contact us at
        <a href="mailto:prima@primavip.co" class="text-primary-600 hover:text-primary-500">prima@primavip.co</a>,
        and our team will assist.
    </p>

    <x-slot name="footerActions">
        <x-filament::button x-on:click="$dispatch('close-modal', { id: 'cutoff-venue-{{ $venue->id }}' })">
            Close
        </x-filament::button>
    </x-slot>
</x-filament::modal>

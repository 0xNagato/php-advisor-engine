<x-filament::modal id="bookings-disabled-modal" alignment="center" width="md">
    <div class="text-center">
        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
        </div>
        <h3 class="mb-2 text-lg font-medium text-gray-900">Soon!</h3>
        <p class="text-sm text-gray-500">
            {{ config('app.bookings_disabled_message') }}
        </p>
    </div>

    <x-slot name="footer">
        <div class="flex justify-center">
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id: 'bookings-disabled-modal' })">
                Close
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>

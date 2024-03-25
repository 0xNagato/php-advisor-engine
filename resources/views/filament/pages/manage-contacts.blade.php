<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full mt-4">Save Contacts</x-filament::button>
    </form>
</x-filament-panels::page>

<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            Save Times
        </x-filament::button>
    </form>
</x-filament-panels::page>

<x-admin.simple>
    <x-filament-panels::form wire:submit="validateCode">
        {{ $this->form }}
        <div>
            <x-filament::button type="submit" class="w-full">
                Submit
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-admin.simple>

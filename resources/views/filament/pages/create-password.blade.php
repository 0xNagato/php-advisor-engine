<x-admin.simple>
    <div class="mb-4 -mt-4 text-sm text-center text-gray-600">
        {{ $this->getSubheading() }}
    </div>
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}
        <x-filament::button type="submit" class="w-full">
            Set Password & Continue
        </x-filament::button>
    </x-filament-panels::form>
</x-admin.simple>

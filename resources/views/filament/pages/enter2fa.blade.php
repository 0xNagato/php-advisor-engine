<x-filament-panels::page>
    <div>
        <x-filament::button type="button" size="sm" outlined wire:click="regenerateCode">
            Resend Code
        </x-filament::button>
    </div>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
        <div>
            @error('code') <span class="error">{{ $message }}</span> @enderror
        </div>
        <div>
            <x-filament::button type="submit" size="sm">
                Submit
            </x-filament::button>
        </div>
        <div>{{ $this->tries }}</div>
    </x-filament-panels::form>
</x-filament-panels::page>

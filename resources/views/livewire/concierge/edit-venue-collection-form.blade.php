<div>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end space-x-3">
            <x-filament::button type="button" color="gray" wire:click="$dispatch('close-modal')">
                Cancel
            </x-filament::button>
            <x-filament::button type="submit">
                Update Collection
            </x-filament::button>
        </div>
    </form>
</div>

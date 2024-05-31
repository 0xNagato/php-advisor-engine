<x-filament-widgets::widget>
    <form wire:submit.prevent="submit">
        {{ $this->form }}

        <x-filament::button type="submit" color="primary" class="w-full mt-4">
            Submit Special Request
        </x-filament::button>
    </form>
</x-filament-widgets::widget>

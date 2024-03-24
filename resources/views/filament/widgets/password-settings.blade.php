<x-filament-widgets::widget>
    <x-filament::section :aside="true">
        <form wire:submit="save">
            {{ $this->form }}
            <div class="text-right mt-6">
                <x-filament::button type="submit" form="submit" class="w-full">
                    Update Password
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>

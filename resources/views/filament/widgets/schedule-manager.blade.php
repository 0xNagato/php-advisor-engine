<x-filament-widgets::widget class="flex flex-col gap-4">
    <x-filament::section aside>
        <form wire:submit.prevent="submit" class="space-y-6">

            {{ $this->form }}

            <div class="text-right">
                <x-filament::button type="submit" form="submit" class="w-full">
                    Save Schedule
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>

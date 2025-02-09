<x-filament-panels::page>
    <x-loading-overlay />

    <div class="space-y-4">
        <div class="bg-white rounded-lg shadow">
            <pre x-data="{
                scroll() {
                    $nextTick(() => {
                        this.$el.scrollTop = this.$el.scrollHeight;
                    });
                }
            }" x-init="scroll()" @chat-updated.window="scroll()" style="scrollbar-width: thin;"
                class="min-w-full p-4 overflow-x-auto font-mono text-xs text-gray-700 whitespace-pre-wrap h-96">{{ $chatHistory }}</pre>
        </div>

        <form wire:submit.prevent="submitRequest" class="space-y-4">
            <input type="text" wire:model.defer="inputMessage"
                placeholder="{{ $confirmationStep ? 'Type to modify request or press Enter to confirm...' : 'Type your request here...' }}"
                class="block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                wire:loading.attr="disabled">

            <div class="flex justify-end gap-2">
                @if ($confirmationStep)
                    <x-filament::button wire:click="confirmTask" wire:loading.attr="disabled" type="button"
                        color="success" icon="heroicon-m-check" wire:target="confirmTask"
                        wire:loading.attr.class="opacity-50">
                        Confirm Task
                    </x-filament::button>
                @endif

                <x-filament::button type="submit" wire:loading.attr="disabled" icon="heroicon-m-paper-airplane"
                    wire:target="submitRequest" wire:loading.attr.class="opacity-50">
                    Submit
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>

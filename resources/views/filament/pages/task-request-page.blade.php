<x-filament-panels::page>
    <x-loading-overlay />

    <div class="space-y-3">
        <div class="bg-white rounded-lg shadow">
            <pre x-data="{
                scroll() {
                    $nextTick(() => {
                        this.$el.scrollTop = this.$el.scrollHeight;
                    });
                }
            }" x-init="scroll()" @chat-updated.window="scroll()" style="scrollbar-width: thin;"
                class="min-w-full p-4 overflow-x-auto font-mono text-xs text-gray-700 h-96">{!! $chatHistory !!}</pre>
        </div>

        @if ($parsedTask)
            <div class="p-3 bg-white border-l-4 rounded-lg shadow border-primary-500">
                <div class="space-y-2">
                    <div class="pb-1">
                        <h3 class="text-base font-semibold text-gray-900">Task Preview</h3>
                        <p class="text-xs text-gray-500">Review and edit the task details before confirming</p>
                    </div>

                    <div class="space-y-1.5">
                        <div>
                            <span class="text-xs font-medium text-gray-500">Name</span>
                            <input type="text" wire:model.live="parsedTask.name"
                                class="block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
                        </div>

                        <div>
                            <span class="text-xs font-medium text-gray-500">Category</span>
                            <input type="text" wire:model.live="parsedTask.category"
                                class="block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
                        </div>

                        <div>
                            <span class="text-xs font-medium text-gray-500">Description</span>
                            <textarea wire:model.live="parsedTask.notes" rows="3"
                                class="block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                        </div>

                        @if ($parsedTask['technical_context'])
                            <div>
                                <span class="text-xs font-medium text-gray-500">Technical Context</span>
                                <textarea wire:model.live="parsedTask.technical_context" rows="3"
                                    class="block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit.prevent="submitRequest" class="space-y-3">
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

        @if ($taskUrl)
            <div>
                <x-filament::button tag="a" href="{{ $taskUrl }}" target="_blank" rel="noopener noreferrer"
                    icon="heroicon-m-arrow-top-right-on-square" color="success" class="justify-center w-full">
                    Open Task in Asana
                </x-filament::button>
            </div>
        @endif
    </div>
</x-filament-panels::page>

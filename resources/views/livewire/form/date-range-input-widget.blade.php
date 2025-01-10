<x-filament-widgets::widget>
    <div class="flex">
        <div class="flex space-x-2 items-center">
            <input type="date" wire:model="startDate"
                   class="border border-gray-300 rounded-[5px] px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <input type="date" wire:model="endDate"
                   class="border border-gray-300 rounded-[5px] px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <button wire:click="updateDateRange"
                    class="px-3 py-2 bg-primary-600 text-white rounded-[5px]">
                Apply
            </button>
        </div>
    </div>
</x-filament-widgets::widget>

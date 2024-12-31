<div x-data="{
    search: '',
    open: false,
    items: @js($items),
    filteredItems() {
        if (!this.search) return this.items;
        return this.items.filter(item =>
            item[{{ json_encode($displayField) }}].toLowerCase().includes(this.search.toLowerCase())
        );
    }
}">
    <label class="block mb-2 text-sm text-gray-700">{{ $label }}</label>
    <div class="relative">
        <input type="text" x-model="search" @focus="open = true" @click.away="open = false"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="{{ $placeholder }}">

        <div x-show="open" x-transition
            class="absolute z-10 w-full mt-1 overflow-y-auto bg-white border border-gray-300 rounded-lg shadow-lg max-h-60">
            <template x-for="item in filteredItems()" :key="item[{{ json_encode($valueField) }}]">
                <button type="button" @click="search = item[{{ json_encode($displayField) }}]; open = false"
                    wire:click="$set('{{ $wireModel }}', item[{{ json_encode($valueField) }}])"
                    class="w-full px-4 py-2 text-left hover:bg-gray-100"
                    x-text="item[{{ json_encode($displayField) }}]">
                </button>
            </template>
            <div x-show="filteredItems().length === 0" class="px-4 py-2 text-sm text-gray-500">
                No items found
            </div>
        </div>
    </div>
    @if ($error)
        <span class="mt-1 text-xs text-red-600">{{ $error }}</span>
    @endif
</div>

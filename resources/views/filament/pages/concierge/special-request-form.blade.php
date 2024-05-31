<!--suppress ALL -->
<x-filament-panels::page>
    <div x-data="{
        tabSelected: 1,
        tabId: $id('tabs'),
        tabButtonClicked(tabButton) {
            this.tabSelected = tabButton.id.replace(this.tabId + '-', '');
            this.tabRepositionMarker(tabButton);
        },
        tabRepositionMarker(tabButton) {
            this.$refs.tabMarker.style.width = tabButton.offsetWidth + 'px';
            this.$refs.tabMarker.style.height = tabButton.offsetHeight + 'px';
            this.$refs.tabMarker.style.left = tabButton.offsetLeft + 'px';
        },
        tabContentActive(tabContent) {
            return this.tabSelected == tabContent.id.replace(this.tabId + '-content-', '');
        }
    }" x-init="tabRepositionMarker($refs.tabButtons.firstElementChild);" class="relative w-full">

        <div x-ref="tabButtons"
            class="relative inline-grid items-center justify-center w-full h-10 grid-cols-2 p-1 mb-0.5 text-gray-500 bg-gray-100 rounded-lg select-none">
            <button :id="$id(tabId)" @click="tabButtonClicked($el);" type="button"
                class="relative z-20 inline-flex items-center justify-center w-full h-8 px-3 text-sm font-medium transition-all rounded-md cursor-pointer whitespace-nowrap">
                Recent Requests
            </button>
            <button :id="$id(tabId)" @click="tabButtonClicked($el);" type="button"
                class="relative z-20 inline-flex items-center justify-center w-full h-8 px-3 text-sm font-medium transition-all rounded-md cursor-pointer whitespace-nowrap">
                Create Request
            </button>
            <div x-ref="tabMarker" class="absolute left-0 z-10 w-1/2 h-full duration-300 ease-out" x-cloak>
                <div class="w-full h-full bg-white rounded-md shadow-sm"></div>
            </div>
        </div>
        <div class="relative w-full mt-2 content">
            <div :id="$id(tabId + '-content')" x-show="tabContentActive($el)" class="relative" x-cloak>
                <livewire:special-request.special-request-list :concierge-id="auth()->user()->concierge->id" />
            </div>

            <div :id="$id(tabId + '-content')" x-show="tabContentActive($el)" class="relative">
                <livewire:special-request.create-special-request-form />
            </div>
        </div>
    </div>

</x-filament-panels::page>

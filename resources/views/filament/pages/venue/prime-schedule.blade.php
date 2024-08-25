<x-filament::page>
    <div x-data="{
        tabSelected: 1,
        tabId: $id('prime-tabs'),
        initializeTab() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('primeTab');
            this.tabSelected = tabParam ? parseInt(tabParam) : 1;
            this.$nextTick(() => this.tabRepositionMarker(this.$refs.tabButtons.children[this.tabSelected - 1]));
        },
        tabButtonClicked(tabButton) {
            this.tabSelected = parseInt(tabButton.id.replace(this.tabId + '-', ''));
            this.tabRepositionMarker(tabButton);
            this.updateUrl();
        },
        tabRepositionMarker(tabButton) {
            this.$refs.tabMarker.style.width = tabButton.offsetWidth + 'px';
            this.$refs.tabMarker.style.height = tabButton.offsetHeight + 'px';
            this.$refs.tabMarker.style.left = tabButton.offsetLeft + 'px';
        },
        tabContentActive(tabNumber) {
            return this.tabSelected == tabNumber;
        },
        updateUrl() {
            const url = new URL(window.location);
            url.searchParams.set('primeTab', this.tabSelected);
            window.history.pushState({}, '', url);
        }
    }"
         x-init="initializeTab();

    window.addEventListener('popstate', initializeTab);

    Livewire.on('navigate', () => {
        initializeTab();
    });"
         class="relative w-full"
         @tab-changed.window="tabSelected = $event.detail.tab; tabRepositionMarker($refs.tabButtons.children[tabSelected - 1]);">

        <div x-ref="tabButtons"
             class="relative inline-grid items-center justify-center w-full h-12 grid-cols-2 p-1 mb-4 bg-gray-100 rounded-lg select-none">
            <button :id="$id(tabId) + '-1'" @click="tabButtonClicked($el);" type="button"
                    class="relative z-20 inline-flex items-center justify-center w-full h-10 px-3 text-sm font-medium transition-all rounded-md cursor-pointer whitespace-nowrap"
                    :class="{'text-white bg-indigo-700': tabSelected == 1, 'text-gray-700 hover:text-indigo-600': tabSelected != 1}">
                Weekly
            </button>

            <button :id="$id(tabId) + '-2'" @click="tabButtonClicked($el);" type="button"
                    class="relative z-20 inline-flex items-center justify-center w-full h-10 px-3 text-sm font-medium transition-all rounded-md cursor-pointer whitespace-nowrap"
                    :class="{'text-white bg-indigo-700': tabSelected == 2, 'text-gray-700 hover:text-indigo-600': tabSelected != 2}">
                Upcoming
            </button>
            <div x-ref="tabMarker" class="absolute left-0 z-10 w-1/2 h-full duration-300 ease-out" x-cloak>
                <div class="w-full h-full bg-indigo-700 rounded-md shadow-sm"></div>
            </div>
        </div>
        <div class="relative w-full content">
            <div :id="$id(tabId + '-content-1')" x-bind:class="{'hidden': !tabContentActive(1)}" class="relative">
                <livewire:venue.weekly-prime-schedule/>
            </div>

            <div :id="$id(tabId + '-content-2')" x-bind:class="{'hidden': !tabContentActive(2)}" class="relative">
                <livewire:venue.upcoming-prime-schedule/>
            </div>
        </div>
    </div>
</x-filament::page>

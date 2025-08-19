<x-filament-panels::page>
    <div class="space-y-2">
        <!-- Filters Above Tabs -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="fi-fo-field-wrp">
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-3 justify-between">
                            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                    Search Concierges
                                </span>
                            </label>
                        </div>
                        <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                            <input type="text"
                                   wire:model.live.debounce.500ms="data.search"
                                   placeholder="Search by name, email, phone, or hotel"
                                   x-on:keyup="$dispatch('updatePendingFilters', { search: $wire.data.search, date_filter: $wire.data.date_filter, start_date: $wire.data.start_date, end_date: $wire.data.end_date })"
                                   class="fi-input block w-full border-none bg-transparent py-1.5 px-3 text-base text-gray-950 placeholder-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder-gray-400 dark:text-white dark:placeholder-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder-gray-500 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                </div>

                <div class="fi-fo-field-wrp">
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-3 justify-between">
                            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                    Data Range
                                </span>
                            </label>
                        </div>
                        <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                            <select wire:model.live="data.date_filter"
                                    wire:change="resetTable"
                                    x-on:change="$dispatch('updatePendingFilters', { search: $wire.data.search, date_filter: $wire.data.date_filter, start_date: $wire.data.start_date, end_date: $wire.data.end_date })"
                                    class="fi-select-input block w-full border-none bg-transparent py-1.5 pe-8 ps-3 text-base text-gray-950 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6">
                                <option value="all_time">All Time</option>
                                <option value="date_range">Date Range</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="fi-fo-field-wrp" x-show="$wire.data.date_filter === 'date_range'" x-transition>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-3 justify-between">
                            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                    Start Date
                                </span>
                            </label>
                        </div>
                        <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                            <input type="date"
                                   wire:model.live="data.start_date"
                                   wire:change="resetTable"
                                   x-on:change="$dispatch('updatePendingFilters', { search: $wire.data.search, date_filter: $wire.data.date_filter, start_date: $wire.data.start_date, end_date: $wire.data.end_date })"
                                   class="fi-input block w-full border-none bg-transparent py-1.5 px-3 text-base text-gray-950 placeholder-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder-gray-400 dark:text-white dark:placeholder-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder-gray-500 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                </div>

                <div class="fi-fo-field-wrp" x-show="$wire.data.date_filter === 'date_range'" x-transition>
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-3 justify-between">
                            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                    End Date
                                </span>
                            </label>
                        </div>
                        <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                            <input type="date"
                                   wire:model.live="data.end_date"
                                   wire:change="resetTable"
                                   x-on:change="$dispatch('updatePendingFilters', { search: $wire.data.search, date_filter: $wire.data.date_filter, start_date: $wire.data.start_date, end_date: $wire.data.end_date })"
                                   class="fi-input block w-full border-none bg-transparent py-1.5 px-3 text-base text-gray-950 placeholder-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder-gray-400 dark:text-white dark:placeholder-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder-gray-500 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
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
                 class="relative inline-grid items-center justify-center w-full h-10 grid-cols-2 p-1 text-gray-500 bg-gray-100 rounded-lg select-none">
                <button :id="$id(tabId)" @click="tabButtonClicked($el);" type="button"
                        class="relative z-[11] inline-flex items-center justify-center w-full h-8 px-3 text-sm font-medium transition-all rounded-md cursor-pointer whitespace-nowrap">
                    Concierges
                </button>
                <button :id="$id(tabId)" @click="tabButtonClicked($el);" type="button"
                        class="relative z-[11] inline-flex items-center justify-center w-full h-8 px-3 text-sm font-medium transition-all rounded-md cursor-pointer whitespace-nowrap">
                    Pending Concierges
                </button>
                <div x-ref="tabMarker" class="absolute left-0 z-10 w-1/2 h-full duration-300 ease-out" x-cloak>
                    <div class="w-full h-full bg-white rounded-md shadow-sm"></div>
                </div>
            </div>
                    <div class="relative w-full mt-2 content">
                <div :id="$id(tabId + '-content')" x-show="tabContentActive($el)" class="relative">
                    {{ $this->table }}
{{--                    <livewire:concierge.list-concierges-table />--}}
                </div>

                <div :id="$id(tabId + '-content')" x-show="tabContentActive($el)" class="relative" x-cloak>
                    <livewire:concierge.list-pending-concierges-table />
                </div>

            </div>
        </div>
    </div>
</x-filament-panels::page>

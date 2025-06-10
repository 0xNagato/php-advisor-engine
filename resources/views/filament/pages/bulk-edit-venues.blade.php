<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters Section --}}
        <div class="p-6 bg-white rounded-lg shadow">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Filters</h3>

            <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-4">
                {{-- Status Filter --}}
                <div>
                    <label for="statusFilter" class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                    <select wire:model.live="statusFilter" id="statusFilter"
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        @foreach ($this->availableStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Region Filter --}}
                <div>
                    <label for="regionFilter" class="block mb-1 text-sm font-medium text-gray-700">Region</label>
                    <select wire:model.live="regionFilter" id="regionFilter"
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Regions</option>
                        @foreach ($this->availableRegions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search Filter --}}
                <div>
                    <label for="searchFilter" class="block mb-1 text-sm font-medium text-gray-700">Search</label>
                    <input wire:model.live.debounce.300ms="searchFilter" type="text" id="searchFilter"
                        placeholder="Search venues..."
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                {{-- Per Page --}}
                <div>
                    <label for="perPage" class="block mb-1 text-sm font-medium text-gray-700">Items per page</label>
                    <select wire:model.live="perPage" id="perPage"
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex space-x-2">
                    <button wire:click="applyFilters"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Apply Filters
                    </button>
                    <button wire:click="resetFilters"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Reset
                    </button>
                </div>

                {{-- Pagination Info --}}
                <div class="text-sm text-gray-700">
                    Showing {{ ($this->currentPage - 1) * $this->perPage + 1 }} to
                    {{ min($this->currentPage * $this->perPage, $this->venues->total()) }} of
                    {{ $this->venues->total() }} venues
                </div>
            </div>
        </div>

        {{-- Pagination Controls (Top) --}}
        @if ($this->venues->hasPages())
            <div class="px-6 py-3 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div class="flex space-x-2">
                        <button wire:click="previousPage" @if ($this->currentPage <= 1) disabled @endif
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>

                        @for ($i = 1; $i <= $this->venues->lastPage(); $i++)
                            <button wire:click="goToPage({{ $i }})"
                                class="inline-flex items-center px-3 py-1 border text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ $i === $this->currentPage ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' }}">
                                {{ $i }}
                            </button>
                        @endfor

                        <button wire:click="nextPage" @if (!$this->venues->hasMorePages()) disabled @endif
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Update Button at Top --}}
        @if ($this->venues->count() > 0)
            <div class="p-6 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Bulk Edit Venues</h3>
                        <p class="mt-1 text-sm text-gray-600">Edit multiple venues at once. Changes are saved when you
                            click "Update All Venues".</p>
                    </div>

                    <div class="flex space-x-4">
                        <button type="button" wire:click="resetChanges"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset Changes
                        </button>

                        <button type="button" wire:click="save"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Update All Venues
                        </button>
                    </div>
                </div>
            </div>

            {{-- Form Section --}}
            <form wire:submit="save">
                {{ $this->form }}

                {{-- Action Buttons at Bottom --}}
                <div class="p-6 mt-6 bg-white rounded-lg shadow">
                    <div class="flex justify-end space-x-4">
                        <button type="button" wire:click="resetChanges"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset Changes
                        </button>

                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Update All Venues
                        </button>
                    </div>
                </div>
            </form>
        @else
            <div class="p-12 text-center bg-white rounded-lg shadow">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No venues found</h3>
                <p class="mt-1 text-sm text-gray-500">No venues match your current filters.</p>
                <div class="mt-6">
                    <button wire:click="resetFilters"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Reset Filters
                    </button>
                </div>
            </div>
        @endif

        {{-- Pagination Controls (Bottom) --}}
        @if ($this->venues->hasPages())
            <div class="px-6 py-3 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div class="flex space-x-2">
                        <button wire:click="previousPage" @if ($this->currentPage <= 1) disabled @endif
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>

                        @for ($i = 1; $i <= $this->venues->lastPage(); $i++)
                            <button wire:click="goToPage({{ $i }})"
                                class="inline-flex items-center px-3 py-1 border text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ $i === $this->currentPage ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' }}">
                                {{ $i }}
                            </button>
                        @endfor

                        <button wire:click="nextPage" @if (!$this->venues->hasMorePages()) disabled @endif
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>

                    <div class="text-sm text-gray-700">
                        Page {{ $this->currentPage }} of {{ $this->venues->lastPage() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

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
        },
        showEditForm: false,
        venueId: null,
        venueName: '',
        currentBooking: null
    }" x-init="tabRepositionMarker($refs.tabButtons.firstElementChild);" class="relative w-full"
        x-on:toggle-edit-form.window="showEditForm = $event.detail.state">

        <!-- Modal open event handler -->
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('open-bulk-edit-modal', (data) => {
                    window.dispatchEvent(new CustomEvent('open-modal', {
                        detail: {
                            id: 'bulk-edit-bookings-modal'
                        }
                    }));
                });
            });
        </script>

        <div x-ref="tabButtons"
            class="relative inline-grid items-center justify-center w-full h-10 grid-cols-2 p-1 text-gray-500 bg-gray-100 rounded-lg select-none">
            <button :id="$id(tabId)" @click="tabButtonClicked($el);" type="button"
                class="relative z-[11] inline-flex items-center justify-center w-full h-8 px-3 text-sm font-medium transition-all rounded-md cursor-pointer whitespace-nowrap">
                Venues
            </button>
            <button :id="$id(tabId)" @click="tabButtonClicked($el);" type="button"
                class="relative z-[11] inline-flex items-center justify-center w-full h-8 px-3 text-sm font-medium transition-all rounded-md cursor-pointer whitespace-nowrap">
                Pending Venues
            </button>
            <div x-ref="tabMarker" class="absolute left-0 z-10 w-1/2 h-full duration-300 ease-out" x-cloak>
                <div class="w-full h-full bg-white rounded-md shadow-sm"></div>
            </div>
        </div>
        <div class="relative w-full mt-2 content">
            <div :id="$id(tabId + '-content')" x-show="tabContentActive($el)" class="relative">
                {{ $this->table }}
            </div>

            <div :id="$id(tabId + '-content')" x-show="tabContentActive($el)" class="relative" x-cloak>
                <livewire:venue.list-pending-venues-table />
            </div>

        </div>
    </div>

    <x-filament::modal id="bulk-edit-bookings-modal" wire:key="bulk-edit-bookings-modal" width="7xl">
        <x-slot name="heading">
            <div class="flex items-center">
                <h2 class="text-xl font-bold tracking-tight">
                    Manage Bookings for <span class="text-primary-600">{{ $this->currentVenueName ?? 'Venue' }}</span>
                </h2>
            </div>
        </x-slot>

        <div class="px-6 pt-4 pb-6">
            <div class="flex items-end justify-start w-full gap-3 pb-4 mb-5 border-b">
                <div>
                    <label for="start-date" class="block mb-1 text-xs font-medium text-gray-600">Start Date</label>
                    <input type="date" id="start-date"
                        class="px-3 py-1 text-sm border rounded-md h-9 w-44 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        wire:model.live="startDate">
                </div>

                <div>
                    <label for="end-date" class="block mb-1 text-xs font-medium text-gray-600">End Date</label>
                    <input type="date" id="end-date"
                        class="px-3 py-1 text-sm border rounded-md h-9 w-44 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        wire:model.live="endDate">
                </div>
                
                <div>
                    <label for="customer-search" class="block mb-1 text-xs font-medium text-gray-600">Customer Search</label>
                    <input type="text" id="customer-search" placeholder="Name, email or phone"
                        class="px-3 py-1 text-sm border rounded-md h-9 w-64 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        wire:model.live="customerSearch">
                </div>

                <button type="button"
                    class="px-5 py-1 text-sm font-medium text-white rounded-md h-9 bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    wire:click="filterBookings">
                    Filter
                </button>
                <button type="button"
                    class="px-4 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md h-9 hover:bg-gray-50 focus:outline-none"
                    wire:click="resetFilter">
                    Reset
                </button>
            </div>
            
            <!-- Bulk ID Status Update Tool -->
            <div class="p-4 mb-5 border rounded-lg bg-gray-50">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Bulk ID Status Update</h3>
                    <div class="text-xs text-gray-500">
                        Enter multiple booking IDs to update their status
                    </div>
                </div>
                
                <form wire:submit.prevent="bulkUpdateBookingStatuses">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label for="bulk-ids" class="block mb-1 text-xs font-medium text-gray-600">Booking IDs</label>
                            <textarea id="bulk-ids" 
                                placeholder="Enter IDs separated by commas, spaces, or new lines"
                                class="w-full h-20 px-3 py-2 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                wire:model="bulkIdsInput"></textarea>
                            <div class="mt-1 text-xs text-gray-500">
                                Example: 1234, 5678, 9012
                            </div>
                        </div>
                        
                        <div>
                            <label for="bulk-status" class="block mb-1 text-xs font-medium text-gray-600">Status to Apply</label>
                            <select id="bulk-status"
                                class="w-full h-9 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                wire:model="bulkStatus">
                                <option value="">Select Status</option>
                                <option value="{{ \App\Enums\BookingStatus::CANCELLED->value }}">
                                    {{ \App\Enums\BookingStatus::CANCELLED->label() }}</option>
                                <option value="{{ \App\Enums\BookingStatus::NO_SHOW->value }}">
                                    {{ \App\Enums\BookingStatus::NO_SHOW->label() }}</option>
                            </select>
                            
                            <div class="flex justify-end mt-6">
                                <button type="submit"
                                    class="px-3 py-1 text-sm font-medium text-white rounded-md shadow-sm bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    Update Status
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Edit form -->
            <div class="p-4 border rounded-lg bg-gray-50">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Edit Booking Details</h3>
                    <div class="text-xs text-gray-500">
                        Click on a booking below to edit
                    </div>
                </div>

                <form wire:submit.prevent="editSelectedBookings">
                    <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                        <div>
                            <label for="guest_first_name" class="block mb-1 text-xs font-medium text-gray-600">First
                                Name</label>
                            <input type="text" id="guest_first_name" wire:model="bulkEditData.guest_first_name"
                                @disabled(empty($selectedBookings))
                                class="w-full h-8 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                        </div>

                        <div>
                            <label for="guest_last_name" class="block mb-1 text-xs font-medium text-gray-600">Last
                                Name</label>
                            <input type="text" id="guest_last_name" wire:model="bulkEditData.guest_last_name"
                                @disabled(empty($selectedBookings))
                                class="w-full h-8 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                        </div>

                        <div>
                            <label for="guest_email" class="block mb-1 text-xs font-medium text-gray-600">Email</label>
                            <input type="email" id="guest_email" wire:model="bulkEditData.guest_email"
                                @disabled(empty($selectedBookings))
                                class="w-full h-8 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                        </div>

                        <div>
                            <label for="guest_phone" class="block mb-1 text-xs font-medium text-gray-600">Phone</label>
                            <input type="tel" id="guest_phone" wire:model="bulkEditData.guest_phone"
                                @disabled(empty($selectedBookings))
                                class="w-full h-8 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                        </div>

                        <div>
                            <label for="guest_count" class="block mb-1 text-xs font-medium text-gray-600">Guest
                                Count</label>
                            <select id="guest_count" wire:model="bulkEditData.guest_count" @disabled(empty($selectedBookings))
                                class="w-full h-8 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                @foreach ($this->getAllowedGuestCounts() as $count)
                                    <option value="{{ $count }}">{{ $count }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-x-4">
                            <div>
                                <label for="booking_date" class="block mb-1 text-xs font-medium text-gray-600">Booking
                                    Date</label>
                                <input type="date" id="booking_date" wire:model="bulkEditData.booking_date"
                                    @disabled(empty($selectedBookings))
                                    class="w-full h-8 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                            </div>
                            <div>
                                <label for="booking_time" class="block mb-1 text-xs font-medium text-gray-600">Booking
                                    Time</label>
                                <select id="booking_time" wire:model="bulkEditData.booking_time"
                                    @disabled(empty($selectedBookings))
                                    class="w-full h-8 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                    <option value="">Select Time</option>
                                    @foreach ($this->timeSlots as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 mt-3 gap-x-4 gap-y-3">
                        <div>
                            <label for="status" class="block mb-1 text-xs font-medium text-gray-600">Status</label>
                            <select id="status" wire:model="bulkEditData.status" @disabled(empty($selectedBookings))
                                class="w-full h-8 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                <option value="">Select Status</option>
                                <option value="{{ \App\Enums\BookingStatus::CONFIRMED->value }}">
                                    {{ \App\Enums\BookingStatus::CONFIRMED->label() }}</option>
                                <option value="{{ \App\Enums\BookingStatus::CANCELLED->value }}">
                                    {{ \App\Enums\BookingStatus::CANCELLED->label() }}</option>
                                <option value="{{ \App\Enums\BookingStatus::NO_SHOW->value }}">
                                    {{ \App\Enums\BookingStatus::NO_SHOW->label() }}</option>
                                <option value="{{ \App\Enums\BookingStatus::VENUE_CONFIRMED->value }}">
                                    {{ \App\Enums\BookingStatus::VENUE_CONFIRMED->label() }}</option>
                                <option value="{{ \App\Enums\BookingStatus::REFUNDED->value }}">
                                    {{ \App\Enums\BookingStatus::REFUNDED->label() }}</option>
                                <option value="{{ \App\Enums\BookingStatus::PARTIALLY_REFUNDED->value }}">
                                    {{ \App\Enums\BookingStatus::PARTIALLY_REFUNDED->label() }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center mt-3">
                        <input type="checkbox" id="send_confirmation" wire:model="bulkEditData.send_confirmation"
                            @disabled(empty($selectedBookings))
                            class="w-4 h-4 border-gray-300 rounded text-primary-600 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <label for="send_confirmation" class="ml-2 text-xs font-medium text-gray-600">
                            Send Confirmation Email to Guests
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" wire:click="clearEditForm"
                            class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-3 py-1 text-sm font-medium text-white rounded-md shadow-sm bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            {{ empty($selectedBookings) ? 'disabled' : '' }}>
                            {{ empty($selectedBookings) ? 'Select a Booking' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bookings table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700 border">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 uppercase">Date</th>
                            <th class="px-3 py-2 uppercase">Guest Information</th>
                            <th class="px-3 py-2 uppercase">Guest Count</th>
                            <th class="px-3 py-2 uppercase">Status</th>
                            <th class="px-3 py-2 text-center uppercase">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->filteredBookings as $booking)
                            <tr class="border-b hover:bg-gray-100 cursor-pointer {{ in_array($booking->id, $selectedBookings) ? 'bg-primary-50 border-primary-100' : '' }}"
                                wire:click="populateEditFormFromBooking({{ $booking->id }})">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($booking->booking_at)->format('M j, Y g:ia') }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $booking->guest_name }}</span>
                                        <span class="text-xs text-gray-500">{{ $booking->guest_email }}</span>
                                        @if ($booking->guest_phone)
                                            <span class="text-xs text-gray-500">{{ $booking->guest_phone }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2">{{ $booking->guest_count }}</td>
                                <td class="px-3 py-2">
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full
                                        {{ $booking->status === \App\Enums\BookingStatus::CONFIRMED
                                            ? 'bg-green-100 text-green-800'
                                            : ($booking->status === \App\Enums\BookingStatus::CANCELLED
                                                ? 'bg-red-100 text-red-800'
                                                : 'bg-gray-100 text-gray-800') }}">
                                        {{ $booking->status->label() }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center" onclick="event.stopPropagation();">
                                    <a href="{{ \App\Filament\Resources\BookingResource::getUrl('view', ['record' => $booking->id]) }}"
                                        target="_blank"
                                        class="inline-flex items-center justify-center text-primary-600 hover:text-primary-800"
                                        title="View Booking Details">
                                        View

                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-400"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="mt-2 text-sm font-medium">No bookings found</span>
                                        <p class="mt-1 text-xs text-gray-500">Try adjusting your filter criteria</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end">
                <button type="button"
                    class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-color-gray gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-gray-950/10 dark:ring-white/20 fi-ac-btn-action"
                    wire:click="$dispatch('close-modal', { id: 'bulk-edit-bookings-modal' })">
                    Close
                </button>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>

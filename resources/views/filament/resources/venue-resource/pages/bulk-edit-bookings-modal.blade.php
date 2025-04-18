<div>
    <div class="bg-gray-100 p-4 mb-4 rounded-lg">
        <h3 class="font-medium text-lg mb-2">Debug Information</h3>
        <div class="bg-white p-3 rounded shadow-sm overflow-auto max-h-32 text-xs">
            <pre>{{ json_encode(session('debug_bookings') ?? [], JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>

    @if (isset($bookings) && $bookings->count() > 0)
        <p class="text-sm text-gray-500 mb-4">Showing {{ $bookings->count() }} bookings for {{ $venue->name }}</p>

        <table class="w-full text-sm text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="p-2">
                        <input type="checkbox" id="select-all-bookings" class="rounded border-gray-300"
                            wire:click="toggleAllBookings">
                    </th>
                    <th class="p-2">ID</th>
                    <th class="p-2">Date</th>
                    <th class="p-2">Guest</th>
                    <th class="p-2">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($bookings as $booking)
                    <tr class="hover:bg-gray-50">
                        <td class="p-2">
                            <input type="checkbox" id="booking-{{ $booking->id }}" value="{{ $booking->id }}"
                                wire:model.live="selectedBookings" class="rounded border-gray-300 booking-checkbox">
                        </td>
                        <td class="p-2">{{ $booking->id ?? 'N/A' }}</td>
                        <td class="p-2">
                            @if (isset($booking->booking_at))
                                {{ \Carbon\Carbon::parse($booking->booking_at)->format('M j, Y g:ia') }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="p-2">{{ $booking->guest_name ?? 'N/A' }}</td>
                        <td class="p-2">{{ $booking->status ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end mt-4">
            <button type="button" class="px-4 py-2 bg-primary-600 text-white rounded-md shadow-sm"
                wire:click="$dispatch('open-edit-selected-modal')" @if (empty($selectedBookings)) disabled @endif>
                Edit Selected Bookings ({{ count($selectedBookings ?? []) }})
            </button>
        </div>
    @else
        <div class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg">
            <h3 class="mt-2 text-sm font-medium text-gray-900">No bookings found</h3>
            <p class="mt-1 text-sm text-gray-500">
                This venue doesn't have any bookings yet.
            </p>
        </div>
    @endif
</div>

<x-slot name="footer">
    <div class="flex justify-end">
        <button type="button"
            class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-color-gray gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-gray-950/10 dark:ring-white/20 fi-ac-btn-action"
            x-on:click="$dispatch('close-modal', { id: 'bulk-edit-bookings-modal' })">
            Close
        </button>
    </div>
</x-slot>

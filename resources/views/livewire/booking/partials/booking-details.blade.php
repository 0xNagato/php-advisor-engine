<div class="space-y-2">
    <div class="mb-4 text-sm font-medium text-gray-500">
        Current Booking Details
    </div>

    <div class="grid grid-cols-2 gap-4 text-xs">
        <div>
            <span class="font-medium text-gray-500">Guest:</span>
            <span class="text-xs">{{ $details['guest_name'] }}</span>
        </div>

        <div>
            <span class="font-medium text-gray-500">Venue:</span>
            <span class="text-xs">{{ $details['venue_name'] }}</span>
        </div>

        <div>
            <span class="font-medium text-gray-500">Date:</span>
            <span class="text-xs">{{ $details['booking_date'] }}</span>
        </div>

        <div>
            <span class="font-medium text-gray-500">Current Time:</span>
            <span class="text-xs">{{ $details['current_time'] }}</span>
        </div>
    </div>
</div>

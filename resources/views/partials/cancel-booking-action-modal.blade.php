<div class="text-sm text-center">
    <p class="p-1 mb-2 text-xs font-semibold text-red-600 bg-red-100 border border-red-300 rounded-md">
        This action will be logged and cannot be undone.
    </p>
    <p class="text-lg font-semibold">{{ $this->record->guest_name }}</p>
    <p><strong>Venue:</strong> {{ $this->record->venue->name }}</p>
    <p><strong>Guest Count:</strong> {{ $this->record->guest_count }}</p>
    <p><strong>Booking Time:</strong> {{ $this->record->booking_at->format('M d, Y h:i A') }}</p>
</div>

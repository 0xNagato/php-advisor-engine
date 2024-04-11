<div class='flex flex-col gap-1 text-xs w-full'>

    <div class="font-semibold flex items-center gap-1">
        <div>{{ $record->guest_name }} ({{ $record->guest_count }})</div>
        
        @if($record->status === \App\Enums\BookingStatus::CONFIRMED)
            <x-heroicon-s-check-circle class="h-4 w-4 text-green-700"/>
        @else
            <x-heroicon-s-clock class="h-4 w-4 text-yellow-700"/>
        @endif

    </div>
    @if($record->guest_email)
        <div>
            {{ $record->guest_email }}
        </div>
    @endif
    <div>
        {{ phone($record->guest_phone, ['US', 'CA'], \libphonenumber\PhoneNumberFormat::NATIONAL) }}
    </div>

    <div class="mt-1 flex gap-2">
        <div>{{ $record->booking_at->format('M j, Y g:ia') }}</div>
    </div>
</div>

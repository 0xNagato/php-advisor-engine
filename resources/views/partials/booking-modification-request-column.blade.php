@php use App\Enums\BookingStatus; @endphp
@php use Carbon\Carbon;use libphonenumber\PhoneNumberFormat; @endphp
<div class='flex flex-col gap-1 text-xs w-full' data-cy="booking-card">
    <div class="font-semibold flex items-center gap-1">
        <div>
            {{ $record->booking->venue->name }}
        </div>

        @if ($record->booking->hasActiveModificationRequest())
            <x-heroicon-s-clock class="h-4 w-4 -mt-0.5 text-gray-400" title="Pending Modification Request" />
        @else
            <x-heroicon-s-check-circle class="h-4 w-4 -mt-0.5 text-green-600" title="Modification Request Approved" />
        @endif

    </div>
    <div class="flex items-center gap-1">
        <div class="font-semibold">{{ $record->booking->guest_name }}:</div>
        <div>{{ formatInternationalPhoneNumber($record->booking->guest_phone) }}</div>
    </div>

    <div class="flex items-center gap-1">
        <div class="font-semibold">Booking:</div>
        <div>{{ $record->booking->booking_at->format('M j, Y g:ia') }}</div>
    </div>

    <div class="flex items-center gap-1">
        <div class="font-semibold ">Current Details:</div>
        <div class="text-xs text-gray-900">
            <span class="font-semibold hidden md:inline">Date:</span>
            {{ Carbon::parse($record->original_booking_at)->format('M j, Y') }}
        </div>
        <div class="text-xs text-gray-900">
            <span class="font-semibold hidden md:inline">Time:</span>
            {{ $record->formatted_original_time }}
        </div>
        <div class="text-xs text-gray-900">
            <span class="font-semibold hidden md:inline">Party Size:</span>
            {{ $record->original_guest_count }} guests
        </div>
    </div>

    <div class="flex items-center gap-1">
        <div class="font-semibold">Requested Changes:</div>
        <div class="text-xs text-gray-900">
            <span class="font-semibold hidden md:inline">Date:</span>
            <span
                class="{{ Carbon::parse($record->original_booking_at)->ne(Carbon::parse($record->request_booking_at)) ? 'text-red-600 font-semibold' : '' }}">
            {{ Carbon::parse($record->request_booking_at)->format('M j, Y') }}
            </span>
        </div>
        <div class="text-xs text-gray-900">
            <span class="font-semibold hidden md:inline">Time:</span>
            <span class="{{ $record->original_time !== $record->requested_time ? 'text-red-600 font-semibold' : '' }}">
                {{ $record->formatted_requested_time }}
            </span>
        </div>
        <div class="text-xs text-gray-900">
            <span class="font-semibold hidden md:inline">Party Size:</span>
            <span
                class="{{ $record->original_guest_count !== $record->requested_guest_count ? 'text-red-600 font-semibold' : '' }}">
                {{ $record->requested_guest_count }} guests
            </span>
        </div>
    </div>
</div>

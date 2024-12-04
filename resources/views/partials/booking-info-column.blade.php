@php use App\Enums\BookingStatus; @endphp
@php use libphonenumber\PhoneNumberFormat; @endphp
<div class='flex flex-col w-full gap-1 text-xs' data-cy="booking-card">
    <div class="flex items-center gap-1 font-semibold">
        <div>
            {{ $record->venue->name }}
        </div>

        @if ($record->status === BookingStatus::REFUNDED || $record->status === BookingStatus::PARTIALLY_REFUNDED)
            <x-heroicon-s-arrow-path-rounded-square class="h-4 w-4 -mt-0.5 text-red-600" />
        @elseif ($record->venue_confirmed_at)
            <x-heroicon-s-check-circle class="h-4 w-4 -mt-0.5 text-green-600" />
        @else
            <x-heroicon-s-clock class="h-4 w-4 -mt-0.5 text-gray-400" />
        @endif

    </div>
    <div class="flex items-center gap-1">
        <div class="font-semibold">{{ $record->guest_name }}:</div>
        <div>{{ formatInternationalPhoneNumber($record->guest_phone) }}</div>
    </div>

    <div class="flex items-center gap-1">
        <div class="font-semibold">Booking:</div>
        <div>{{ $record->booking_at->format('M j, Y g:ia') }}</div>
    </div>

    @if (!$record->venue_confirmed_at && auth()->user()->hasActiveRole('super_admin'))
        <div class="mt-2 font-semibold">Venue Contacts:</div>
        @if ($record->venue && $record->venue->contacts)
            @foreach ($record->venue->contacts->where('use_for_reservations', true) as $contact)
                <div class="flex gap-1">
                    <div class="font-semibold">{{ $contact->contact_name }}:</div>
                    <div>{{ formatInternationalPhoneNumber($contact->contact_phone) }}</div>
                </div>
            @endforeach
        @else
            <div>No contacts available</div>
        @endif
    @endif
</div>

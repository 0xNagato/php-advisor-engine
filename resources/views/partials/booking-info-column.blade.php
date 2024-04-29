@php use App\Enums\BookingStatus; @endphp
@php use libphonenumber\PhoneNumberFormat; @endphp
<div class='flex flex-col gap-1 text-xs w-full'>

    <div class="font-semibold flex items-center gap-1">
        @unless(auth()->user()->hasRole('restaurant'))
            <div>
                {{ $record->restaurant->restaurant_name }}
            </div>
        @endunless

        @if($record->restaurant_confirmed_at)
            <x-heroicon-s-check-circle class="h-4 w-4 -mt-0.5 text-green-600"/>
        @else
            <x-heroicon-s-clock class="h-4 w-4 -mt-0.5 text-gray-400"/>
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

    @if(!$record->restaurant_confirmed_at && auth()->user()->hasRole('super_admin'))
        <div class="mt-2 font-semibold">Restaurant Contacts:</div>
        @foreach($record->restaurant->contacts->where('use_for_reservations', true) as $contact)
            <div class="flex gap-1 ml-1">
                <div class="font-semibold">{{ $contact->contact_name }}:</div>
                <div>{{ formatInternationalPhoneNumber($contact->contact_phone) }}</div>
            </div>
        @endforeach
    @endif
</div>

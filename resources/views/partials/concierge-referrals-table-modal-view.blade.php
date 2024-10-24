@php use App\Filament\Resources\BookingResource\Pages\ViewBooking;use Carbon\Carbon; @endphp
<div class="p-0 space-y-4">
    <!-- Current Information -->
    <div>
        <dl class="grid grid-cols-2 text-xs sm:text-sm gap-x-4 gap-y-2">
            <dt class="font-semibold">Referral Earnings:</dt>
            <dd>{{ $referralsEarnings }}</dd>
            <dt class="font-semibold">Referral Bookings:</dt>
            <dd>{{ $referralsBookings }}</dd>
            <dt class="font-semibold">Referrals Invited:</dt>
            <dd>{{ $concierge->referrals_count }}</dd>
            <dt class="font-semibold">Referrals Confirmed:</dt>
            <dd>{{ $concierge->concierges_count }}</dd>
        </dl>
    </div>

    <!-- Recent Bookings -->
    <livewire:concierge.list-referrals :concierge="$concierge" />
</div>

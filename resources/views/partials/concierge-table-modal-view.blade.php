@php use App\Filament\Resources\BookingResource\Pages\ViewBooking;use Carbon\Carbon; @endphp
<div class="p-0 space-y-4">
    <!-- Current Information -->
    <div>
        <dl class="grid grid-cols-2 text-xs sm:text-sm gap-x-4 gap-y-2">
            <dt class="font-semibold">Date Joined:</dt>
            <dd>{{ $secured_at ? $secured_at->format('D M j, Y') : 'N/A' }}</dd>
            <dt class="font-semibold">Referred By:</dt>
            <dd>
                @if ($referral_url)
                    <a href="{{ $referral_url }}" class="text-indigo-600 underline font-semibold">
                        {{ $referrer_name }}
                    </a>
                @else
                    {{ $referrer_name }}
                @endif
            </dd>
            <dt class="font-semibold">Direct Bookings:</dt>
            <dd>{{ $bookings_count }}</dd>
            <dt class="font-semibold">Referral Bookings:</dt>
            <dd>{{ $referralsBookings }}</dd>
            <dt class="font-semibold">Earnings:</dt>
            <dd>{{ $earningsInUSD }}</dd>
            <dt class="font-semibold">Avg. Earning per Direct Booking:</dt>
            <dd>{{ $avgEarnPerBookingInUSD }}</dd>
            <dt class="font-semibold">Last Login:</dt>
            <dd>{{ $last_login ? Carbon::parse($last_login)->diffForHumans() : 'Never' }}</dd>
        </dl>
    </div>

    <!-- Recent Bookings -->
    <livewire:concierge.recent-bookings :concierge="$concierge"/>
</div>

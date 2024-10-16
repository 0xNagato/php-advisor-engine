@php
    use App\Filament\Resources\BookingResource\Pages\ViewBooking;
    use App\Models\Referral;
    use Carbon\Carbon;
    use Illuminate\Support\Str;
@endphp
<div class="p-0 space-y-4">
    <!-- Current Information -->
    <div>
        <dl class="grid grid-cols-2 text-xs sm:text-sm gap-x-4 gap-y-2">
            <dt class="font-semibold">Date Joined:</dt>
            <dd>{{ $secured_at ? $secured_at->format('D M j, Y') : 'N/A' }}</dd>
            <dt class="font-semibold">Percentage:</dt>
            <dd>{{ $percentage }}%</dd>
            <dt class="font-semibold">Earned:</dt>
            <dd>{{ money($total_earned, 'USD') }}</dd>
            <dt class="font-semibold">Bookings:</dt>
            <dd>{{ $bookings_count }}</dd>
            <dt class="font-semibold">Last Login:</dt>
            <dd>{{ $last_login ? Carbon::parse($last_login)->diffForHumans() : 'Never' }}</dd>
        </dl>
    </div>

    <!-- Recent Referrals -->
    <livewire:partner.referral-table :partner="$partner" />
</div>

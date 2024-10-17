@php
    use Carbon\Carbon;
@endphp
<div class="p-0 space-y-4">
    <!-- Current Information -->
    <div>
        <dl class="grid grid-cols-2 text-xs sm:text-sm gap-x-4 gap-y-2">
            <dt class="font-semibold">Code:</dt>
            <dd>{{ $vipCode->code }}</dd>
            <dt class="font-semibold">Bookings:</dt>
            <dd>{{ $vipCode->bookings_count }}</dd>
            <dt class="font-semibold">Earnings:</dt>
            <dd>{{ money($vipCode->total_earnings_in_u_s_d* 100, 'USD') }}</dd>
            <dt class="font-semibold">Created at:</dt>
            <dd>{{ Carbon::parse($vipCode->created_at, auth()->user()->timezone)->format('M d, Y g:i A') }}</dd>
        </dl>
    </div>

    <!-- Recent Bookings -->
    <livewire:vip-code.recent-bookings :vip-code="$vipCode" />
</div>

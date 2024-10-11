<x-filament-panels::page>
    <div class="grid flex-1 auto-cols-fr gap-y-4 -mt-4">
        <div class="text-sm space-y-2">
            <p>
                You may offer your clients the ability to book their own reservations through PRIMA by using your
                private
                referral link provided below.
            </p>
            <p>
                Any reservations booked by your customers will automatically track commissions to your account, even if
                they
                continue to make future bookings directly with PRIMA.
            </p>
        </div>
        <livewire:concierge.vip-codes-table key="{{ now() }}" :table-filters="$filters"/>
        <div>
            <h2 class="font-semibold mb-1">Vanity VIP Codes</h2>
            <p class="text-sm">
                You may also create a custom vanity URL for your clients, just enter the custom VIP code you’d like to
                use
                below.
            </p>
        </div>
        {{ $this->form }}
    </div>
</x-filament-panels::page>

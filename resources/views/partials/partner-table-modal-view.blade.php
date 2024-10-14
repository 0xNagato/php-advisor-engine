@php use App\Filament\Resources\BookingResource\Pages\ViewBooking; @endphp
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
            <dd>{{ $last_login ? \Carbon\Carbon::parse($last_login)->diffForHumans() : 'Never' }}</dd>
        </dl>
    </div>

    <!-- Recent Bookings -->
    <div>
        <h3 class="mb-2 text-base font-semibold sm:text-lg">Referrals</h3>
        @if ($referrals->count() > 0)
            <!-- Mobile view (column structure) -->
            <div class="grid grid-cols-2 gap-2 sm:hidden">
                @foreach ($referrals as $referral)
                    <div
                        class="flex flex-col justify-between h-full p-2 space-y-1 bg-white border border-gray-200 rounded-lg shadow">
                        <div>
                            <div class="text-xs font-semibold">
                                {{ $referral->user->name }}
                            </div>
                            <div class="text-xs text-gray-600">
                                {{ $referral->type }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop view (table structure) -->
            <div class="hidden overflow-x-auto sm:block">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Name
                            </th>
                            <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Type
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($referrals as $referral)
                            <tr class="cursor-pointer hover:bg-gray-100"
                                onclick="window.location='{{ $referral->viewRoute }}'">
                                <td class="px-3 py-2 text-sm whitespace-nowrap">{{ $referral->user->name }}
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    {{ $referral->type }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No referrals found.</p>
        @endif
    </div>
</div>

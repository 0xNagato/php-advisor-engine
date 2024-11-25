@php use App\Filament\Resources\BookingResource\Pages\ViewBooking; @endphp
<div class="p-0 space-y-4">
    <!-- Current Information -->
    <div>
        <dl class="grid grid-cols-2 text-xs sm:text-sm gap-x-4 gap-y-2">
            <dt class="font-semibold">User:</dt>
            <dd>
                @if (auth()->user()->hasRole('super_admin'))
                    <a href="{{ route('filament.admin.resources.users.edit', ['record' => $user]) }}"
                        class="text-indigo-600 hover:underline">
                        {{ $user->name }}
                    </a>
                @else
                    N/A
                @endif
            </dd>
            <dt class="font-semibold">Date Joined:</dt>
            <dd>{{ $secured_at ? $secured_at->format('D M j, Y') : 'N/A' }}</dd>
            <dt class="font-semibold">Referred By:</dt>
            <dd>{{ $referrer_name }}</dd>
            <dt class="font-semibold">Earned:</dt>
            <dd>{{ $earningsInUSD }}</dd>
            <dt class="font-semibold">Bookings:</dt>
            <dd>{{ $bookings_count }}</dd>
            <dt class="font-semibold">Last Login:</dt>
            <dd>{{ $last_login ? \Carbon\Carbon::parse($last_login)->diffForHumans() : 'Never' }}</dd>

            <dt class="font-semibold">Venue Contacts:</dt>
            <dd></dd>
            @if ($contacts)
                @foreach ($contacts->where('use_for_reservations', true) as $contact)
                    <dt class="ml-1 font-semibold">{{ $contact->contact_name }}:</dt>
                    <dd class="ml-2">
                        <a class="text-indigo-600 underline" href="tel:{{ $contact->contact_phone }}">
                            {{ formatInternationalPhoneNumber($contact->contact_phone) }}
                        </a>
                    </dd>
                @endforeach
            @else
                <dd>No contacts available</dd>
            @endif
        </dl>
    </div>

    <!-- Recent Bookings -->
    <div>
        <h3 class="mb-2 text-base font-semibold sm:text-lg">Recent Bookings</h3>
        @if ($recentBookings->count() > 0)
            <!-- Mobile view (column structure) -->
            <div class="grid grid-cols-2 gap-2 sm:hidden">
                @foreach ($recentBookings as $booking)
                    <div onclick="window.location='{{ route('filament.admin.resources.bookings.view', $booking) }}'"
                        class="flex flex-col justify-between h-full p-2 space-y-1 bg-white border border-gray-200 rounded-lg shadow">
                        <div>
                            <div class="text-xs font-semibold">
                                {{ $booking->concierge->user->name }}
                            </div>
                            <div class="text-xs text-gray-600">
                                {{ $booking->booking_at->format('D, M j g:ia') }}
                            </div>
                        </div>
                        <div class="text-xs font-semibold">
                            Fee: {{ money($booking->total_fee, $booking->currency) }}
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
                                Id
                            </th>
                            <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Concierge
                            </th>
                            <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Date
                            </th>
                            <th class="px-3 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Fee
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($recentBookings as $booking)
                            <tr class="cursor-pointer hover:bg-gray-100"
                                onclick="window.location='{{ route('filament.admin.resources.bookings.view', $booking) }}'">
                                <td class="px-3 py-2 text-sm whitespace-nowrap">{{ $booking->id }}</td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">{{ $booking->concierge->user->name }}
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    {{ $booking->booking_at->format('D, M j g:ia') }}</td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    {{ money($booking->total_fee, $booking->currency) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No recent bookings found.</p>
        @endif
    </div>
</div>

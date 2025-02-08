@php
    use App\Enums\BookingStatus;
@endphp

<div class="grid w-full grid-cols-1 gap-6 col-span-full md:grid-cols-2 xl:grid-cols-3">
    <div class="flex items-center justify-end gap-3 col-span-full">
        <span class="text-sm text-gray-600">Viewing analytics based on:</span>
        <button wire:click="toggleDateType"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500">
            {{ $showBookingTime ? 'Booking Time' : 'Creation Time' }}
        </button>
    </div>

    <!-- Top Venues -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Top Venues</h3>
        </div>
        <div class="p-4 space-y-3">
            @foreach ($this->getAnalytics()['topVenues'] as $venue)
                <a href="{{ route('filament.admin.pages.booking-search', [
                    'filters' => [
                        'venue_search' => $venue['name'],
                        'show_booking_time' => $showBookingTime,
                        'start_date' => $this->filters['startDate'],
                        'end_date' => $this->filters['endDate'],
                        'status' => [BookingStatus::CONFIRMED->value, BookingStatus::VENUE_CONFIRMED->value],
                    ],
                ]) }}"
                    class="flex items-center justify-between p-1 rounded hover:bg-gray-50">
                    <span class="text-sm text-gray-600">{{ $venue['name'] }}</span>
                    <span class="font-medium text-gray-900">{{ $venue['booking_count'] }} bookings</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Prime vs Non-Prime -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Prime vs Non-Prime</h3>
        </div>
        <div class="p-4 space-y-3">
            @foreach ($this->getAnalytics()['primeAnalysis'] as $isPrime => $data)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $isPrime ? 'Prime' : 'Non-Prime' }}</span>
                    <div class="text-right">
                        <div class="font-medium text-gray-900">{{ $data['count'] }} bookings</div>
                        <div class="text-sm text-gray-500">
                            @if ($isPrime)
                                Avg Fee: ${{ number_format($data['avg_fee_usd'], 2) }}<br>
                            @endif
                            Avg Platform: ${{ number_format($data['avg_platform_earnings_usd'], 2) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Popular Times -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Popular Times</h3>
        </div>
        <div class="p-4 space-y-3">
            @foreach ($this->getAnalytics()['popularTimes'] as $time)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $time['time_slot'] }}</span>
                    <span class="font-medium text-gray-900">{{ $time['booking_count'] }} bookings</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Lead Time Analysis -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Lead Time</h3>
        </div>
        <div class="p-4 space-y-3">
            @foreach ($this->getAnalytics()['leadTimeAnalysis'] as $leadTime)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $leadTime['lead_time'] }}</span>
                    <span class="font-medium text-gray-900">{{ $leadTime['count'] }} bookings</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Party Sizes -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Party Sizes</h3>
        </div>
        <div class="p-4 space-y-3">
            @foreach ($this->getAnalytics()['partySizes'] as $size)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $size['guest_count'] }} guests</span>
                    <span class="font-medium text-gray-900">{{ $size['count'] }} bookings</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Day of Week Analysis -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Day of Week</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-7 gap-2">
                @foreach ($this->getAnalytics()['dayAnalysis'] as $day)
                    <div class="text-center">
                        <div class="text-xs font-medium text-gray-600">{{ substr($day['day_name'], 0, 3) }}</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $day['booking_count'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Calendar Day Analysis -->
    <div class="col-span-1 bg-white rounded-lg shadow md:col-span-2 xl:col-span-3">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Calendar Day</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($this->getAnalytics()['calendarDayAnalysis'] as $day)
                    <a href="{{ route('filament.admin.pages.booking-search', [
                        'filters' => [
                            'start_date' => \Carbon\Carbon::parse($day['date'])->format('Y-m-d'),
                            'end_date' => \Carbon\Carbon::parse($day['date'])->format('Y-m-d'),
                            'show_booking_time' => $showBookingTime,
                            'status' => [BookingStatus::CONFIRMED->value, BookingStatus::VENUE_CONFIRMED->value],
                        ],
                    ]) }}"
                        class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $day['date'] }}</div>
                            <div class="text-xs text-gray-500">{{ $day['day_name'] }}</div>
                        </div>
                        <div class="text-lg font-semibold text-gray-900">{{ $day['booking_count'] }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

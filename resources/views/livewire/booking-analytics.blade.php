<div class="grid w-full grid-cols-1 gap-6 col-span-full md:grid-cols-2 xl:grid-cols-3">
    <!-- Top Venues -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Top Venues</h3>
        </div>
        <div class="p-4 space-y-3">
            @foreach ($this->getAnalytics()['topVenues'] as $venue)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $venue['name'] }}</span>
                    <span class="font-medium text-gray-900">{{ $venue['booking_count'] }} bookings</span>
                </div>
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
                        <div class="text-sm text-gray-500">Avg: ${{ number_format($data['avg_fee_usd'], 2) }}</div>
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
</div>

<div class="space-y-6">
    {{-- Conversion Funnel --}}
    <div class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4">
                </path>
            </svg>
            Conversion Funnel
        </h3>

        <div class="grid grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ number_format($sessions) }}</div>
                <div class="text-sm text-gray-600">Sessions</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ number_format($bookings) }}</div>
                <div class="text-sm text-gray-600">Confirmed Bookings</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-emerald-600">{{ $conciergeEarnings }}</div>
                <div class="text-sm text-gray-600">Earnings</div>
            </div>
        </div>

        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700">Conversion Rate:</span>
                <span class="text-lg font-bold text-green-600">{{ $conversionRate }}%</span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
                {{ $bookings }} bookings from {{ number_format($sessions) }} sessions
            </div>
        </div>
    </div>

    {{-- Query Parameter Analytics Table --}}
    @if ($paramAnalytics->count() > 0)
        <div class="bg-white rounded-lg border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h2M9 5a2 2 0 002 2v10a2 2 0 01-2 2M9 5a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H11a2 2 0 01-2-2V7">
                    </path>
                </svg>
                Query Parameter Performance
            </h3>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Parameter
                            </th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Value
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sessions
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Bookings
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Earnings
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Conv %
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($paramAnalytics as $analytics)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3">
                                    <code class="text-sm font-mono text-gray-700 bg-gray-100 px-2 py-1 rounded">
                                        {{ $analytics['key'] }}
                                    </code>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="text-sm text-gray-900">
                                        {{ $analytics['value'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-medium text-gray-900">
                                    {{ number_format($analytics['sessions']) }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-medium text-gray-900">
                                    {{ number_format($analytics['bookings']) }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-medium text-green-600">
                                    @if(isset($analytics['earnings_by_currency']) && !empty($analytics['earnings_by_currency']))
                                        @php
                                            $formatted = [];
                                            foreach($analytics['earnings_by_currency'] as $currency => $amount) {
                                                $formatted[] = money($amount, $currency);
                                            }
                                        @endphp
                                        {{ implode(', ', $formatted) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-medium">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $analytics['conversion'] >= 50 ? 'bg-green-100 text-green-800' : ($analytics['conversion'] >= 25 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $analytics['conversion'] }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    @endif

    {{-- Date Range Info --}}
    <div class="text-center text-sm text-gray-500">
        Analytics for {{ $dateRange }}
    </div>
</div>
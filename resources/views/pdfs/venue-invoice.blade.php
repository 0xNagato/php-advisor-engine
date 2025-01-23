<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 13px;
        }
    </style>
</head>

<body class="bg-white">
    <div class="min-h-[11in]">
        <!-- Header with Background -->
        <div class="px-6 py-6 text-white bg-indigo-600">
            <div class="max-w-4xl mx-auto">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <span class="mt-3 mb-1 text-2xl font-bold">INVOICE</span>
                        <p class="text-base opacity-90">{{ $venue->name }}</p>
                        <div class="mt-2">
                            <div class="text-xs opacity-75">Invoice Number</div>
                            <div class="text-base font-medium">{{ $invoiceNumber }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div>
                            <span class="inline-block px-4 py-1 mb-4 text-2xl font-bold bg-indigo-800 rounded-lg">
                                PRIMA
                            </span>
                        </div>
                        <div>
                            <div class="text-xs opacity-75">Date</div>
                            <div class="mb-2 text-base font-medium">{{ now()->format('M j, Y') }}</div>
                            <div class="text-xs opacity-75">Date Range</div>
                            <div class="text-base font-medium">
                                {{ $startDate->format('M j, Y') }} - {{ $endDate->format('M j, Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="max-w-4xl px-6 py-6 mx-auto">
            <!-- Non-Prime Bookings Section -->
            @if ($nonPrimeBookings->isNotEmpty())
                <div class="mb-6">
                    <h2 class="mb-2 text-lg font-semibold text-gray-800">Non-Prime Bookings</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="px-3 py-2 text-xs font-medium text-left text-gray-600">Date</th>
                                    <th class="px-3 py-2 text-xs font-medium text-left text-gray-600">Reference</th>
                                    <th class="px-3 py-2 text-xs font-medium text-center text-gray-600">Guests</th>
                                    <th class="px-3 py-2 text-xs font-medium text-right text-gray-600">Incentive Fee
                                    </th>
                                    <th class="px-3 py-2 text-xs font-medium text-right text-gray-600">PRIMA Fee
                                        ({{ \App\Constants\BookingPercentages::NON_PRIME_PROCESSING_FEE_PERCENTAGE }}%)
                                    </th>
                                    <th class="px-3 py-2 text-xs font-medium text-right text-gray-600">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($nonPrimeBookings as $booking)
                                    @php
                                        // Calculate incentive fee (stored in cents)
                                        $incentiveFee = $booking->guest_count * ($venue->non_prime_fee_per_head * 100);

                                        // Calculate PRIMA fee (10% of incentive fee)
                                        $primaFee =
                                            $incentiveFee *
                                            (\App\Constants\BookingPercentages::NON_PRIME_PROCESSING_FEE_PERCENTAGE /
                                                100);

                                        $totalAmount = $primaFee + $incentiveFee;
                                    @endphp
                                    <tr class="border-b border-gray-100">
                                        <td class="px-3 py-2">{{ $booking->booking_at->format('M j, Y') }}</td>
                                        <td class="px-3 py-2">
                                            <div class="flex items-center">
                                                <span class="font-medium">NP-{{ $booking->id }}</span>
                                                <span
                                                    class="px-1.5 py-0.5 ml-2 text-xs font-semibold text-gray-700 bg-gray-100 rounded">Non-Prime</span>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $booking->guest_first_name }} {{ $booking->guest_last_name }}
                                                <br>
                                                {{ $booking->guest_phone }}
                                                @if ($booking->guest_email)
                                                    | {{ $booking->guest_email }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-center">{{ $booking->guest_count }}</td>
                                        <td class="px-3 py-2 font-medium text-right">
                                            {{ money($incentiveFee, $booking->currency) }}
                                        </td>
                                        <td class="px-3 py-2 font-medium text-right">
                                            {{ money($primaFee, $booking->currency) }}
                                        </td>
                                        <td class="px-3 py-2 font-medium text-right">
                                            {{ money($totalAmount, $booking->currency) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50">
                                    <td colspan="2" class="px-4 py-3 text-xs text-gray-500">
                                        Total Bookings: {{ $nonPrimeBookings->count() }} |
                                        Total Guests: {{ $nonPrimeBookings->sum('guest_count') }}
                                    </td>
                                    <td colspan="3" class="px-4 py-3 font-semibold text-right">Non-Prime Total:</td>
                                    <td class="px-4 py-3 font-semibold text-right">
                                        {{ money($nonPrimeTotalAmount, $nonPrimeBookings->first()?->currency ?? 'USD') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Prime Bookings Section -->
            @if ($primeBookings->isNotEmpty())
                <div class="mb-6">
                    <h2 class="mb-2 text-lg font-semibold text-gray-800">Prime Bookings</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="px-3 py-2 text-xs font-medium text-left text-gray-600">Date</th>
                                    <th class="px-3 py-2 text-xs font-medium text-left text-gray-600">Reference</th>
                                    <th class="px-3 py-2 text-xs font-medium text-center text-gray-600">Guests</th>
                                    <th class="px-3 py-2 text-xs font-medium text-right text-gray-600">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($primeBookings as $booking)
                                    <tr class="border-b border-gray-100">
                                        <td class="px-3 py-2">{{ $booking->booking_at->format('M j, Y') }}</td>
                                        <td class="px-3 py-2">
                                            <div class="flex items-center">
                                                <span class="font-medium">P-{{ $booking->id }}</span>
                                                <span
                                                    class="px-1.5 py-0.5 ml-2 text-xs font-semibold text-green-700 bg-green-100 rounded">Prime</span>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $booking->guest_first_name }} {{ $booking->guest_last_name }}
                                                <br>
                                                {{ $booking->guest_phone }}
                                                @if ($booking->guest_email)
                                                    | {{ $booking->guest_email }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-center">{{ $booking->guest_count }}</td>
                                        <td class="px-3 py-2 font-medium text-right">
                                            {{ money(abs($booking->earnings->sum('amount')), $booking->currency) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50">
                                    <td colspan="2" class="px-4 py-3 text-xs text-gray-500">
                                        Total Bookings: {{ $primeBookings->count() }} |
                                        Total Guests: {{ $primeBookings->sum('guest_count') }}
                                    </td>
                                    <td colspan="1" class="px-4 py-3 font-semibold text-right">Prime Total:</td>
                                    <td class="px-4 py-3 font-semibold text-right">
                                        {{ money($primeTotalAmount, $primeBookings->first()?->currency ?? 'USD') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            @if ($primeTotalAmount != 0 || $nonPrimeTotalAmount != 0)
                @php
                    $balance = $primeTotalAmount + $nonPrimeTotalAmount;
                @endphp
                <div class="p-6 text-right rounded-lg bg-indigo-50">
                    <h3 class="text-xl font-semibold text-indigo-900">
                        @if ($balance > 0)
                            PRIMA Owes Venue:
                            <span class="text-indigo-600">
                                {{ money($balance, $primeBookings->first()?->currency ?? ($nonPrimeBookings->first()?->currency ?? 'USD')) }}
                            </span>
                        @else
                            Venue Owes PRIMA:
                            <span class="text-indigo-600">
                                {{ money(abs($balance), $primeBookings->first()?->currency ?? ($nonPrimeBookings->first()?->currency ?? 'USD')) }}
                            </span>
                        @endif
                    </h3>
                </div>
            @endif

            <!-- Footer -->
            <div class="pt-6 mt-8 border-t border-gray-200">
                <div class="text-sm text-gray-600">
                    <p class="mb-4">Please make payment within 15 days of invoice date.</p>

                    <div class="p-6 border border-gray-200 rounded-lg bg-gray-50">
                        <h4 class="mb-3 font-medium">Payment Information</h4>
                        <table class="text-xs">
                            <tr>
                                <td class="pr-3 pb-1.5 text-gray-500">Name:</td>
                                <td class="pb-1.5">PRIMA VIP INC</td>
                            </tr>
                            <tr>
                                <td class="pr-3 pb-1.5 text-gray-500">Account Number:</td>
                                <td class="pb-1.5">822000968917</td>
                            </tr>
                            <tr>
                                <td class="pr-3 pb-1.5 text-gray-500">Account Type:</td>
                                <td class="pb-1.5">Checking</td>
                            </tr>
                            <tr>
                                <td class="pr-3 pb-1.5 text-gray-500">Routing Number (ACH):</td>
                                <td class="pb-1.5">026073150</td>
                            </tr>
                            <tr>
                                <td class="pr-3 pb-1.5 text-gray-500">Swift/BIC:</td>
                                <td class="pb-1.5">CMFGUS33</td>
                            </tr>
                            <tr>
                                <td class="pr-3 text-gray-500 align-top">Bank:</td>
                                <td class="pb-1.5">
                                    Community Federal Savings Bank<br>
                                    89-16 Jamaica Ave<br>
                                    Woodhaven, NY, 11421<br>
                                    United States
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div
                        class="px-4 py-3 mt-6 text-center text-indigo-600 border border-indigo-100 rounded-lg bg-indigo-50">
                        For any questions, please contact your account manager.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

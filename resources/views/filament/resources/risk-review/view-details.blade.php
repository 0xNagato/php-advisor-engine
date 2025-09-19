<div class="space-y-6">
    <!-- Risk Score Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Risk Assessment Summary</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Risk Score</p>
                <p class="text-2xl font-bold @if($booking->risk_score >= 70) text-red-600 @elseif($booking->risk_score >= 30) text-yellow-600 @else text-green-600 @endif">
                    {{ $booking->risk_score }}/100
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Risk Level</p>
                <p class="text-2xl font-bold @if($booking->risk_state === 'hard') text-red-600 @else text-yellow-600 @endif">
                    {{ strtoupper($booking->risk_state) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Risk Reasons -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Risk Indicators</h3>
        @if(is_array($booking->risk_reasons) && count($booking->risk_reasons) > 0)
            <ul class="space-y-2">
                @foreach($booking->risk_reasons as $reason)
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $reason }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">No specific risk reasons recorded.</p>
        @endif
    </div>

    <!-- Booking Details -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Booking Information</h3>
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Guest Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $booking->guest_name ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $booking->guest_email ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $booking->guest_phone ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Party Size</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $booking->guest_count }} guests</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Venue</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $booking->venue?->name ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Booking Date/Time</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $booking->booking_at?->format('M j, Y g:i A') ?? 'N/A' }}</dd>
            </div>
            @if($booking->ip_address)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $booking->ip_address }}</dd>
            </div>
            @endif
            @if($booking->notes)
            <div class="col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer Notes</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $booking->notes }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <!-- Audit Trail -->
    @if($booking->riskAuditLogs->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Risk Audit Trail</h3>
        <div class="space-y-3">
            @foreach($booking->riskAuditLogs->sortByDesc('created_at') as $log)
                <div class="border-l-4 @if($log->event === 'scored') border-blue-500 @elseif($log->event === 'auto_held') border-yellow-500 @else border-gray-500 @endif pl-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ ucfirst(str_replace('_', ' ', $log->event)) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $log->created_at->format('M j, g:i A') }}
                        </p>
                    </div>
                    @if($log->payload && isset($log->payload['score']))
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Score: {{ $log->payload['score'] }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Prior Bookings -->
    @php
        $priorBookings = \App\Models\Booking::where(function($query) use ($booking) {
            $query->where('guest_email', $booking->guest_email)
                  ->orWhere('guest_phone', $booking->guest_phone);
        })
        ->where('id', '!=', $booking->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    @endphp

    @if($priorBookings->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Prior Booking Attempts</h3>
        <div class="space-y-2">
            @foreach($priorBookings as $prior)
                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                    <div class="text-sm">
                        <span class="font-medium">{{ $prior->venue?->name ?? 'Unknown Venue' }}</span>
                        <span class="text-gray-500 dark:text-gray-400 ml-2">{{ $prior->booking_at?->format('M j, Y') }}</span>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full @if($prior->status === 'confirmed') bg-green-100 text-green-800 @elseif($prior->status === 'cancelled') bg-red-100 text-red-800 @else bg-gray-100 text-gray-800 @endif">
                        {{ ucfirst($prior->status->value) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
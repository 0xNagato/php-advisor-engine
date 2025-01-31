<x-layouts.simple-wrapper>
    <div class="max-w-2xl mx-auto">
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-4 sm:py-5 sm:px-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">
                    Booking Modification Request
                </h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    Please review the requested changes below.
                </p>
            </div>

            <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
                <dl class="space-y-3">
                    <div class="pb-3 mb-3 border-b border-gray-200">
                        <dt class="text-sm font-medium text-gray-500">Venue</dt>
                        <dd class="mt-0.5">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $modificationRequest->booking->venue->name }}
                            </div>
                        </dd>
                    </div>

                    <div class="flex items-baseline justify-between">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Guest Name</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ $modificationRequest->booking->guest_name }}
                            </dd>
                        </div>
                        <div class="text-right">
                            <dt class="text-sm font-medium text-gray-500">Booking Date</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">
                                {{ $modificationRequest->booking->booking_at->format('F j, Y') }}
                            </dd>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-x-4">
                        <div class="p-2.5 bg-gray-50 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">Current Details</dt>
                            <dd class="mt-1.5 space-y-1.5">
                                <div class="text-sm text-gray-900">
                                    <span class="font-medium">Time:</span>
                                    {{ $modificationRequest->formatted_original_time }}
                                </div>
                                <div class="text-sm text-gray-900">
                                    <span class="font-medium">Party Size:</span>
                                    {{ $modificationRequest->original_guest_count }} guests
                                </div>
                            </dd>
                        </div>

                        <div class="p-2.5 bg-red-50 rounded-lg">
                            <dt class="text-sm font-medium">Requested Changes</dt>
                            <dd class="mt-1.5 space-y-1.5">
                                <div class="text-sm text-gray-900">
                                    <span class="font-medium">Time:</span>
                                    <span
                                        class="{{ $modificationRequest->original_time !== $modificationRequest->requested_time ? 'text-red-600 font-medium' : '' }}">
                                        {{ $modificationRequest->formatted_requested_time }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900">
                                    <span class="font-medium">Party Size:</span>
                                    <span
                                        class="{{ $modificationRequest->original_guest_count !== $modificationRequest->requested_guest_count ? 'text-red-600 font-medium' : '' }}">
                                        {{ $modificationRequest->requested_guest_count }} guests
                                    </span>
                                </div>
                            </dd>
                        </div>
                    </div>
                </dl>
            </div>

            @if ($modificationRequest->status === 'pending')
                <div class="px-4 py-4 bg-gray-50 sm:px-6">
                    <div class="flex justify-between gap-x-3">
                        {{ $this->rejectAction }}
                        {{ $this->approveAction }}
                    </div>
                </div>
            @else
                <div class="px-4 py-4 bg-gray-50 sm:px-6">
                    <div
                        class="text-sm font-medium @if ($modificationRequest->status === 'approved') text-success-600 @else text-danger-600 @endif">
                        Request {{ $modificationRequest->status }}
                        @if ($modificationRequest->rejection_reason)
                            <p class="mt-0.5 text-gray-500">
                                Reason: {{ $modificationRequest->rejection_reason }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.simple-wrapper>

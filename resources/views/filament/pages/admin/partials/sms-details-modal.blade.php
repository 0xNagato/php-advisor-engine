<div class="space-y-6">
    {{-- Message Content --}}
    <div>
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Message</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $record->message }}</p>
        </div>
    </div>

    {{-- Basic Details --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Type</h3>
            <span
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                {{ $record->type === 'scheduled' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                {{ ucfirst($record->type) }}
            </span>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Status</h3>
            <span
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                @switch($record->status)
                    @case('scheduled')
                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @break
                    @case('processing')
                        bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @break
                    @case('sent')
                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @break
                    @case('cancelled')
                        bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                        @break
                    @case('failed')
                        bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @break
                    @default
                        bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                @endswitch
            ">
                {{ ucfirst($record->status) }}
            </span>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Total Recipients</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($record->total_recipients) }}</p>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Created By</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->creator?->name ?? 'Unknown' }}</p>
        </div>
    </div>

    {{-- Timing Details --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Scheduled Time</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $record->scheduled_at?->timezone(auth()->user()->timezone ?? config('app.timezone'))->format('M j, Y g:i A') ?? 'Not set' }}
                @if ($record->scheduled_at)
                    <span class="text-xs text-gray-500 dark:text-gray-500 block">
                        ({{ auth()->user()->timezone ?? config('app.timezone') }})
                    </span>
                @endif
            </p>
        </div>

        @if ($record->sent_at)
            <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Sent Time</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $record->sent_at->timezone(auth()->user()->timezone ?? config('app.timezone'))->format('M j, Y g:i A') }}
                    <span class="text-xs text-gray-500 dark:text-gray-500 block">
                        ({{ auth()->user()->timezone ?? config('app.timezone') }})
                    </span>
                </p>
            </div>
        @endif

        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Created</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $record->created_at->timezone(auth()->user()->timezone ?? config('app.timezone'))->format('M j, Y g:i A') }}
                <span class="text-xs text-gray-500 dark:text-gray-500 block">
                    ({{ auth()->user()->timezone ?? config('app.timezone') }})
                </span>
            </p>
        </div>

        @if ($record->updated_at && $record->updated_at != $record->created_at)
            <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Last Updated</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $record->updated_at->timezone(auth()->user()->timezone ?? config('app.timezone'))->format('M j, Y g:i A') }}
                    <span class="text-xs text-gray-500 dark:text-gray-500 block">
                        ({{ auth()->user()->timezone ?? config('app.timezone') }})
                    </span>
                </p>
            </div>
        @endif
    </div>

    {{-- Recipient Types --}}
    @if ($record->recipient_types && count($record->recipient_types) > 0)
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Recipient Types</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($record->recipient_types as $recipientType)
                    @php
                        $displayName = match ($recipientType) {
                            'concierges_with_bookings' => 'Concierges with Bookings',
                            'concierges_active_no_bookings' => 'Active Concierges (No Bookings)',
                            'concierges_inactive' => 'Inactive Concierges',
                            'pending_concierges' => 'Pending Concierges',
                            'partners' => 'Partners',
                            'venues' => 'Venues',
                            default => ucfirst(str_replace('_', ' ', $recipientType)),
                        };
                    @endphp
                    <span
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                        {{ $displayName }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Target Regions --}}
    @if ($record->regions && count($record->regions) > 0)
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Target Regions</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($record->regions as $regionId)
                    @php
                        $region = \App\Models\Region::find($regionId);
                    @endphp
                    @if ($region)
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                            {{ $region->name }}
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    @else
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Target Regions</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">All regions</p>
        </div>
    @endif

    {{-- Recipient Data Summary --}}
    @if ($record->recipient_data && count($record->recipient_data) > 0)
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Recipient Chunks</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Split into {{ count($record->recipient_data) }} chunk(s) of phone numbers for processing
            </p>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                @foreach ($record->recipient_data as $index => $chunk)
                    Chunk {{ $index + 1 }}: {{ count($chunk) }} recipients<br>
                @endforeach
            </div>
        </div>
    @endif
</div>

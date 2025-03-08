@php
    // We need to directly access the properties column from the database
    $properties = json_decode($getRecord()->getRawOriginal('properties'), true);
@endphp

@if (!$properties || !is_array($properties))
    <div class="italic text-gray-500">No details available</div>
@else
    <div class="w-full py-3 space-y-3">
        {{-- Add badges at the top in a row --}}
        <div class="flex items-center gap-2">
            {{-- Time slot info with US-style time --}}
            @if (isset($properties['time']))
                @php
                    $dayInfo = '';
                    if (isset($properties['day_of_week'])) {
                        $dayInfo = ucfirst($properties['day_of_week']);
                    } elseif (isset($properties['booking_date'])) {
                        $dayInfo = 'Date ' . date('M j, Y', strtotime($properties['booking_date']));
                    } else {
                        $dayInfo = 'Unknown day';
                    }

                    // Convert 24h time to 12h am/pm format
                    $time = $properties['time'];
                    $formattedTime = date('g:ia', strtotime($time));
                @endphp
                <div class="font-medium">{{ $formattedTime }} on {{ $dayInfo }}</div>
            @endif

            {{-- Spacer to push badges to the right --}}
            <div class="flex-grow"></div>

            {{-- Type badge --}}
            <span
                class="px-2 py-1 text-xs rounded-full {{ $getRecord()->description === 'Schedule template updated' ? 'bg-primary-100 text-primary-700' : 'bg-warning-100 text-warning-700' }}">
                {{ $getRecord()->description }}
            </span>

            {{-- Bulk edit indicator --}}
            @php
                $isBulkEdit = $properties['bulk_edit'] ?? false;
            @endphp
            @if ($isBulkEdit)
                <span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Bulk Edit</span>
            @else
                <span class="px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">Single Edit</span>
            @endif
        </div>

        {{-- Changes section --}}
        <div class="pt-2 mt-2 border-t">
            <div class="mb-1 font-medium">Changes:</div>

            {{-- For template_update (array format) --}}
            @if ($properties['action'] === 'template_update' && isset($properties['new_data']) && is_array($properties['new_data']))
                @foreach ($properties['new_data'] as $item)
                    @if (isset($item['data']))
                        <div class="p-1 ml-2 text-sm rounded bg-gray-50">
                            @if (isset($item['party_size']))
                                <span class="font-medium">Party of {{ $item['party_size'] }}:</span>
                            @endif

                            @php
                                $changes = [];

                                if (isset($item['data']['is_prime'])) {
                                    $isPrime = $item['data']['is_prime'];
                                    $changes[] =
                                        '<span class="' .
                                        ($isPrime ? 'text-amber-600' : 'text-gray-600') .
                                        '">' .
                                        ($isPrime ? 'Prime Time' : 'Regular Time') .
                                        '</span>';
                                }

                                if (isset($item['data']['is_available'])) {
                                    $isAvailable = $item['data']['is_available'];
                                    $changes[] =
                                        '<span class="' .
                                        ($isAvailable ? 'text-green-600' : 'text-red-600') .
                                        '">' .
                                        ($isAvailable ? 'Available' : 'Not Available') .
                                        '</span>';
                                }

                                if (
                                    isset($item['data']['price_per_head']) &&
                                    $item['data']['price_per_head'] !== null
                                ) {
                                    $changes[] = 'Price: $' . number_format($item['data']['price_per_head'], 2);
                                }

                                if (
                                    isset($item['data']['minimum_spend_per_guest']) &&
                                    $item['data']['minimum_spend_per_guest'] > 0
                                ) {
                                    $changes[] =
                                        'Min Spend: $' . number_format($item['data']['minimum_spend_per_guest'], 2);
                                }

                                if (isset($item['data']['available_tables'])) {
                                    $tables = $item['data']['available_tables'];
                                    $changes[] =
                                        'Tables: ' .
                                        ($tables === '0' ? '<span class="text-red-600">None</span>' : $tables);
                                }
                            @endphp

                            {!! implode(' • ', $changes) !!}
                        </div>
                    @endif
                @endforeach
                {{-- For override_update (object format with party sizes as keys) --}}
            @elseif($properties['action'] === 'override_update' && isset($properties['new_data']) && is_array($properties['new_data']))
                @foreach ($properties['new_data'] as $partySize => $data)
                    @if ($data !== null)
                        <div class="p-1 ml-2 text-sm rounded bg-gray-50">
                            <span class="font-medium">Party of {{ $partySize }}:</span>

                            @php
                                $changes = [];

                                if (isset($data['prime_time'])) {
                                    $isPrime = $data['prime_time'];
                                    $changes[] =
                                        '<span class="' .
                                        ($isPrime ? 'text-amber-600' : 'text-gray-600') .
                                        '">' .
                                        ($isPrime ? 'Prime Time' : 'Regular Time') .
                                        '</span>';
                                }

                                if (isset($data['is_available'])) {
                                    $isAvailable = $data['is_available'];
                                    $changes[] =
                                        '<span class="' .
                                        ($isAvailable ? 'text-green-600' : 'text-red-600') .
                                        '">' .
                                        ($isAvailable ? 'Available' : 'Not Available') .
                                        '</span>';
                                }

                                if (isset($data['price_per_head']) && $data['price_per_head'] !== null) {
                                    $changes[] = 'Price: $' . number_format($data['price_per_head'], 2);
                                }

                                if (isset($data['minimum_spend_per_guest']) && $data['minimum_spend_per_guest'] > 0) {
                                    $changes[] = 'Min Spend: $' . number_format($data['minimum_spend_per_guest'], 2);
                                }

                                if (isset($data['available_tables'])) {
                                    $tables = $data['available_tables'];
                                    $changes[] =
                                        'Tables: ' .
                                        ($tables === 0 || $tables === '0'
                                            ? '<span class="text-red-600">None</span>'
                                            : $tables);
                                }
                            @endphp

                            {!! implode(' • ', $changes) !!}
                        </div>
                    @endif
                @endforeach
            @endif
        </div>

        {{-- Original data section --}}
        @if (isset($properties['original_data']) && !empty($properties['original_data']))
            <div class="pt-2 mt-2 text-sm text-gray-500 border-t">
                <div class="mb-1 font-medium">Previous settings:</div>

                {{-- For template_update (array format) --}}
                @if ($properties['action'] === 'template_update' && is_array($properties['original_data']))
                    @php
                        $count = count($properties['original_data']);
                    @endphp

                    @foreach ($properties['original_data'] as $index => $item)
                        {{-- Only show first 2 items to save space --}}
                        @if ($index >= 2)
                            @if ($index == 2)
                                <div class="ml-2 italic">+ {{ $count - 2 }} more party sizes...</div>
                            @endif
                            @continue
                        @endif

                        @if ($item !== null)
                            @php
                                $originalChanges = [];

                                if (isset($item['prime_time'])) {
                                    $originalChanges[] = $item['prime_time'] ? 'Prime Time' : 'Regular Time';
                                }

                                if (isset($item['is_available'])) {
                                    $originalChanges[] = $item['is_available'] ? 'Available' : 'Not Available';
                                }

                                if (isset($item['price_per_head']) && $item['price_per_head'] !== null) {
                                    $originalChanges[] = 'Price: $' . number_format($item['price_per_head'], 2);
                                }

                                if (isset($item['minimum_spend_per_guest']) && $item['minimum_spend_per_guest'] > 0) {
                                    $originalChanges[] =
                                        'Min Spend: $' . number_format($item['minimum_spend_per_guest'], 2);
                                }

                                if (isset($item['available_tables'])) {
                                    $originalChanges[] = 'Tables: ' . $item['available_tables'];
                                }
                            @endphp

                            @if (!empty($originalChanges))
                                <div class="ml-2">
                                    @if (isset($item['id']))
                                        <span class="opacity-75">ID {{ $item['id'] }}:</span>
                                    @endif
                                    {{ implode(' • ', $originalChanges) }}
                                </div>
                            @endif
                        @endif
                    @endforeach
                    {{-- For override_update (object format with party sizes as keys) --}}
                @elseif($properties['action'] === 'override_update' && is_array($properties['original_data']))
                    @php
                        $partySizes = array_keys($properties['original_data']);
                        $count = count($partySizes);
                    @endphp

                    @foreach ($partySizes as $index => $partySize)
                        {{-- Only show first 2 items to save space --}}
                        @if ($index >= 2)
                            @if ($index == 2)
                                <div class="ml-2 italic">+ {{ $count - 2 }} more party sizes...</div>
                            @endif
                            @continue
                        @endif

                        @php
                            $data = $properties['original_data'][$partySize];
                        @endphp

                        @if ($data === null)
                            <div class="ml-2">Party of {{ $partySize }}: <span class="italic">No previous
                                    data</span></div>
                        @else
                            @php
                                $originalChanges = [];

                                if (isset($data['prime_time'])) {
                                    $originalChanges[] = $data['prime_time'] ? 'Prime Time' : 'Regular Time';
                                }

                                if (isset($data['is_available'])) {
                                    $originalChanges[] = $data['is_available'] ? 'Available' : 'Not Available';
                                }

                                if (isset($data['price_per_head']) && $data['price_per_head'] !== null) {
                                    $originalChanges[] = 'Price: $' . number_format($data['price_per_head'], 2);
                                }

                                if (isset($data['minimum_spend_per_guest']) && $data['minimum_spend_per_guest'] > 0) {
                                    $originalChanges[] =
                                        'Min Spend: $' . number_format($data['minimum_spend_per_guest'], 2);
                                }

                                if (isset($data['available_tables'])) {
                                    $originalChanges[] = 'Tables: ' . $data['available_tables'];
                                }
                            @endphp

                            @if (!empty($originalChanges))
                                <div class="ml-2">
                                    <span class="font-medium">Party of {{ $partySize }}:</span>
                                    {{ implode(' • ', $originalChanges) }}
                                </div>
                            @endif
                        @endif
                    @endforeach
                @endif
            </div>
        @endif
    </div>
@endif

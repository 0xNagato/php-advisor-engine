<div class="filament-widget-card bg-white shadow rounded-lg p-4">
    <div class="space-y-2">
        <h3 class="text-sm font-semibold text-gray-900">🧪 SQL Performance Test</h3>
        
        <div class="text-xs space-y-1">
            <div><strong>Month:</strong> {{ $startMonth ?: 'None' }}</div>
            <div><strong>Count:</strong> {{ $numberOfMonths ?: 'None' }}</div>
            <div><strong>Region:</strong> {{ $region ?: 'All' }}</div>
            <div><strong>Search:</strong> {{ $search ?: 'None' }}</div>
        </div>
        
        <div class="border-t pt-2 text-xs">
            <div class="font-semibold text-blue-600">Query Results:</div>
            @if(count($queryResults))
                @foreach($queryResults as $result)
                    @if(isset($result['error']))
                        <div class="text-red-600">Error: {{ $result['error'] }}</div>
                    @else
                        <div>{{ $result['month'] }}: {{ $result['direct'] }}D/{{ $result['referral'] }}R</div>
                    @endif
                @endforeach
                <div class="font-medium text-purple-600 mt-1">
                    ⚡ Query Time: {{ $queryDuration }}ms
                </div>
            @else
                <div class="text-gray-500">No data</div>
            @endif
        </div>
        
        <div class="border-t pt-2 text-xs text-gray-500">
            <div><strong>Updated:</strong> <span class="font-mono">{{ $lastUpdated ?: 'Never' }}</span></div>
        </div>
        
        <div class="text-xs text-green-600 font-medium">
            ✓ Same SQL as ApexCharts
        </div>
    </div>
</div>
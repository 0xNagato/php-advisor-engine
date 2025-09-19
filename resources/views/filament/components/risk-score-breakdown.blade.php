@php
    $record = $getRecord();
    $metadata = $record->risk_metadata;

    // Laravel Data automatically handles casting - $metadata is a RiskMetadata object or null
    // But let's be defensive in case of older data or edge cases
    $breakdown = [];
    if ($metadata) {
        try {
            $breakdown = $metadata->getFormattedBreakdown();
        } catch (\Exception $e) {
            // Fallback if method fails
            $breakdown = [];
        }
    }
@endphp

<div class="space-y-4">
    @if($metadata)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Overall Risk Score</h4>
                <div class="flex items-center gap-2">
                    <span class="text-3xl font-bold {{ $metadata->totalScore >= 70 ? 'text-red-600' : ($metadata->totalScore >= 30 ? 'text-yellow-600' : 'text-green-600') }}">
                        {{ $metadata->totalScore }}
                    </span>
                    <span class="text-gray-500">/100</span>
                </div>
                <p class="text-sm mt-1 text-gray-600 dark:text-gray-400">
                    @if($metadata && method_exists($metadata, 'getRiskLevel'))
                        {{ $metadata->getRiskLevel() }}
                    @else
                        {{ match(true) {
                            $metadata->totalScore >= 70 => 'High Risk',
                            $metadata->totalScore >= 30 => 'Medium Risk',
                            default => 'Low Risk'
                        } }}
                    @endif
                </p>
            </div>

            @if($metadata->llmUsed)
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-2">AI Analysis Applied</h4>
                    <p class="text-sm text-blue-600 dark:text-blue-400">
                        This score includes AI-powered risk assessment
                    </p>
                </div>
            @endif
        </div>

        @if(!empty($breakdown))
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Raw Score</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Weight</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Weighted Score</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Issues Found</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($breakdown as $category => $data)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $category }}
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $data['score'] >= 50 ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : ($data['score'] >= 20 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400') }}">
                                        {{ $data['score'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-center text-gray-500 dark:text-gray-400">
                                    {{ ($data['weight'] * 100) }}%
                                </td>
                                <td class="px-4 py-3 text-sm text-center font-medium">
                                    <span class="text-gray-900 dark:text-gray-100">
                                        {{ number_format($data['weighted'], 1) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    @if(!empty($data['reasons']))
                                        <ul class="list-disc list-inside space-y-1">
                                            @foreach(array_slice($data['reasons'], 0, 3) as $reason)
                                                <li>{{ $reason }}</li>
                                            @endforeach
                                            @if(count($data['reasons']) > 3)
                                                <li class="text-gray-400">+{{ count($data['reasons']) - 3 }} more...</li>
                                            @endif
                                        </ul>
                                    @else
                                        <span class="text-gray-400 italic">No issues detected</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <td colspan="3" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100 text-right">
                                Total Score:
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="text-lg font-bold {{ $metadata->totalScore >= 70 ? 'text-red-600' : ($metadata->totalScore >= 30 ? 'text-yellow-600' : 'text-green-600') }}">
                                    {{ $metadata->totalScore }}
                                </span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        @if($metadata->analyzedAt)
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                Analyzed at: {{ \Carbon\Carbon::parse($metadata->analyzedAt)->format('M j, Y g:i:s A') }}
            </div>
        @endif
    @else
        <div class="text-gray-500 dark:text-gray-400 text-center py-4">
            No risk metadata available. This booking may have been processed before the detailed breakdown feature was added.
        </div>
    @endif
</div>
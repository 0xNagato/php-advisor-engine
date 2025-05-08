<div class="space-y-6">
    <div class="flex items-center gap-x-3">
        <input type="text" value="{{ $shortUrl }}"
            id="announcement-url"
            class="flex-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
            readonly>
        <x-filament::button
            type="button"
            x-data="{ copied: false }"
            x-on:click="
                navigator.clipboard.writeText($el.previousElementSibling.value);
                copied = true;
                // The visual feedback in the button is sufficient
                setTimeout(() => copied = false, 2000);
            "
        >
            <span x-show="!copied">Copy</span>
            <span x-show="copied" x-cloak class="text-green-500 flex items-center">
                <x-heroicon-m-check class="w-4 h-4 mr-1" />
                Copied!
            </span>
        </x-filament::button>
    </div>
    
    <div>
        <p class="text-sm text-gray-500 mb-3">
            When partners or concierges visit this link, they'll see the full announcement message.
        </p>
        
        <div class="border rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-700">Link Analytics</h3>
                    <span class="text-sm font-medium text-gray-700 flex items-center gap-x-1">
                        <x-heroicon-m-eye class="w-4 h-4" />
                        {{ $totalUniqueVisits }} total unique {{ Str::plural('visit', $totalUniqueVisits) }}
                    </span>
                </div>
            </div>
            
            @if($visitsPerDay->isEmpty())
                <div class="p-4 text-sm text-gray-500 text-center">
                    No visits recorded yet
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Unique Visits
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($visitsPerDay as $visit)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                    {{ $visit['date'] }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 text-right">
                                    {{ $visit['unique_visits'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
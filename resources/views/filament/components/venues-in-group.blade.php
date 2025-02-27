@php
    $venue = $getRecord();
    $venueGroup = $venue->venueGroup;
    $venues = collect([]);

    if ($venueGroup) {
        $venues = $venueGroup->venues;
    }

    // Helper function to get status color
    function getStatusColor($status)
    {
        return match ($status) {
            \App\Enums\VenueStatus::ACTIVE => 'bg-success-100 text-success-800',
            \App\Enums\VenueStatus::PENDING => 'bg-warning-100 text-warning-800',
            \App\Enums\VenueStatus::DRAFT => 'bg-gray-100 text-gray-800',
            \App\Enums\VenueStatus::SUSPENDED => 'bg-danger-100 text-danger-800',
            \App\Enums\VenueStatus::HIDDEN => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
@endphp

<div class="space-y-2">
    <h3 class="text-sm font-medium text-gray-700">Venues</h3>

    @if ($venues->isEmpty())
        <div class="p-2 text-sm text-center text-gray-500 rounded-lg bg-gray-50">
            <p>No venues in this group.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Name
                        </th>
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Region
                        </th>
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Status
                        </th>
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($venues as $index => $groupVenue)
                        <tr
                            class="{{ $groupVenue->id === $venue->id ? 'bg-primary-50' : ($index % 2 === 1 ? 'bg-gray-50' : '') }}">
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="flex items-center space-x-1">
                                    <span class="text-sm font-medium text-gray-900">{{ $groupVenue->name }}</span>
                                    @if ($groupVenue->id === $venue->id)
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800">
                                            Current
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    {{ \App\Models\Region::find($groupVenue->region)?->name ?? 'Unknown' }}
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium {{ getStatusColor($groupVenue->status) }}">
                                    {{ $groupVenue->status->getLabel() }}
                                </span>
                            </td>
                            <td class="px-2 py-2 text-sm whitespace-nowrap">
                                <a href="{{ \App\Filament\Resources\VenueResource::getUrl('edit', ['record' => $groupVenue]) }}"
                                    class="text-primary-600 hover:text-primary-900">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

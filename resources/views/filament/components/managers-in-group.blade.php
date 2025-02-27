@php
    $venue = $getRecord();
    $venueGroup = $venue->venueGroup;
    $managers = collect([]);

    if ($venueGroup) {
        $managers = $venueGroup->managers;
    }
@endphp

<div class="space-y-2">
    <h3 class="text-sm font-medium text-gray-700">Managers</h3>

    @if ($managers->isEmpty())
        <div class="p-2 text-sm text-center text-gray-500 rounded-lg bg-gray-50">
            <p>No managers in this group.</p>
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
                            Current Venue
                        </th>
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Allowed Venues
                        </th>
                        <th scope="col"
                            class="px-2 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($managers as $index => $manager)
                        @php
                            $currentVenueId = $manager->pivot->current_venue_id;
                            $currentVenue = \App\Models\Venue::find($currentVenueId);

                            $allowedVenueIds = json_decode($manager->pivot->allowed_venue_ids ?? '[]', true);
                            $allowedVenues = \App\Models\Venue::whereIn('id', $allowedVenueIds)
                                ->pluck('name')
                                ->toArray();
                        @endphp
                        <tr
                            class="{{ $manager->id === $venueGroup->primary_manager_id ? 'bg-primary-50' : ($index % 2 === 1 ? 'bg-gray-50' : '') }}">
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="flex items-center space-x-1">
                                    <span class="text-sm font-medium text-gray-900">{{ $manager->name }}</span>
                                    @if ($manager->id === $venueGroup->primary_manager_id)
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800">
                                            Primary
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    {{ $currentVenue ? $currentVenue->name : 'None' }}
                                </div>
                            </td>
                            <td class="px-2 py-2">
                                <div class="max-w-xs text-sm text-gray-500 truncate">
                                    @if (empty($allowedVenues))
                                        All venues
                                    @else
                                        {{ implode(', ', $allowedVenues) }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-2 py-2 text-sm whitespace-nowrap">
                                <a href="{{ route('filament.admin.resources.users.edit', ['record' => $manager->id]) }}"
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

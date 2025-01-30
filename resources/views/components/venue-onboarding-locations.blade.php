@props(['locations'])

<div class="space-y-4">
    @foreach ($locations as $location)
        <div class="p-4 border rounded-lg bg-gray-50">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-900">
                    @if ($location->created_venue_id)
                        <a href="{{ \App\Filament\Resources\VenueResource\Pages\ViewVenue::getUrl(['record' => $location->created_venue_id]) }}"
                            class="text-primary-600 hover:text-primary-500">
                            {{ $location->name }}
                        </a>
                    @else
                        {{ $location->name }}
                    @endif
                </h3>
                @if ($location->logo_path)
                    <img src="{{ $location->logo }}" alt="{{ $location->name }} logo" class="object-contain h-16">
                @endif
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-medium text-gray-500">Prime Hours</h4>
                @if ($location->prime_hours && count(array_filter($location->prime_hours)))
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                            @if (!empty($location->prime_hours[$day]))
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs font-medium text-gray-700 capitalize">{{ $day }}</span>
                                    <span class="text-xs text-gray-600">
                                        @php
                                            $hours = collect($location->prime_hours[$day])
                                                ->filter(fn($selected) => $selected)
                                                ->keys()
                                                ->map(
                                                    fn($time) => Carbon\Carbon::createFromFormat(
                                                        'H:i:s',
                                                        $time,
                                                    )->format('g:i A'),
                                                )
                                                ->implode(', ');
                                        @endphp
                                        {{ $hours ?: 'No prime hours' }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-xs text-gray-600">
                        No prime hours submitted
                    </div>
                @endif
            </div>

            @if ($location->use_non_prime_incentive)
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <h4 class="text-xs font-medium text-gray-500">Non-Prime Incentive</h4>
                    <span class="text-sm text-gray-900">${{ number_format($location->non_prime_per_diem, 2) }} per
                        diner</span>
                </div>
            @endif
        </div>
    @endforeach
</div>

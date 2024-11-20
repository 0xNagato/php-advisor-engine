<div class="space-y-4">
    @foreach ($locations as $location)
        <div class="p-4 rounded-lg bg-gray-50">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <span class="text-sm font-medium text-gray-500">Venue Name</span>
                    <p class="mt-1">{{ $location->name }}</p>
                </div>

                @if ($location->logo_path)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Logo</span>
                        <div class="mt-1">
                            <img src="{{ Storage::url($location->logo_path) }}" alt="{{ $location->name }} logo"
                                class="object-contain h-12">
                        </div>
                    </div>
                @endif

                @if ($location->prime_hours)
                    <div class="col-span-full">
                        <span class="text-sm font-medium text-gray-500">Prime Hours</span>
                        <div class="grid grid-cols-2 gap-2 mt-1 md:grid-cols-3 lg:grid-cols-4">
                            @foreach ($location->prime_hours as $day => $hours)
                                <div class="text-sm">
                                    <span class="font-medium">{{ ucfirst($day) }}:</span>
                                    <span>{{ $hours }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($location->use_non_prime_incentive)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Non-Prime Per Diem</span>
                        <p class="mt-1">${{ number_format($location->non_prime_per_diem, 2) }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

@php
    $user = auth()->user();
    $venueGroup = null;

    // Check if user is a concierge and has a venue group
    if ($user && $user->hasActiveRole('concierge')) {
        // Use the direct relationship to get the concierge
        $concierge = $user->concierge;

        // If concierge exists and has a venue group, get the venue group
        if ($concierge && $concierge->venue_group_id) {
            $venueGroup = $concierge->venueGroup;
        }
    }
@endphp

<div class="order-first w-full mr-auto text-left">
    <div class="flex items-center space-x-3">
        <a href="{{ config('app.platform_url') }}">
            <x-filament-panels::logo class="lg:hidden" />
        </a>

        @if ($venueGroup && $venueGroup->logo_path)
            <div class="h-8 border-l border-gray-200"></div>
            <div class="flex items-center">
                <img src="{{ $venueGroup->logo }}" alt="{{ $venueGroup->name }}" class="object-contain w-auto h-8" />
            </div>
        @endif
    </div>
</div>

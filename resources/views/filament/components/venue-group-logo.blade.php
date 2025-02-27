@php
    $venue = $getRecord();
    $venueGroup = $venue->venueGroup;
@endphp

<div class="flex items-center justify-center w-full h-full">
    @if ($venueGroup && $venueGroup->logo_path)
        <div class="p-2 bg-white rounded-lg shadow-sm">
            <img src="{{ $venueGroup->logo }}" alt="{{ $venueGroup->name }}" class="object-contain w-auto h-24 mx-auto" />
        </div>
    @else
        <div class="flex items-center justify-center w-24 h-24 bg-gray-100 rounded-lg">
            <svg class="w-12 h-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
    @endif
</div>

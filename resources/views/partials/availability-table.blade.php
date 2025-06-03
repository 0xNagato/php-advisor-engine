@php
    use App\Enums\VenueStatus;
    use App\Models\Region;
    use App\Models\Venue;
    use App\Models\Cuisine;
    use App\Services\ReservationService;

    // Get tier venues for this region using the new configuration system
    $goldVenues = ReservationService::getVenuesInTier($this->region->id, 1);
    $silverVenues = ReservationService::getVenuesInTier($this->region->id, 2);
    $topTiersLine = false;
    $showVenueModals = config('app.show_venue_modals', true);
@endphp
<div class="relative -mx-4 -mt-4 bg-white border-t sm:mx-0 sm:mt-0">
    <table class="w-full table-auto">
        <thead class="text-xs uppercase">
            <tr class="sticky bg-white border-b shadow {{ $stickyHeaderTopPosition }}">
                <th></th>
                @foreach ($timeslotHeaders as $index => $timeslot)
                    <th
                        class="p-2 pl-4 text-center text-sm font-semibold {{ $loop->first ? 'hidden sm:table-cell' : '' }} {{ $loop->last ? 'hidden sm:table-cell' : '' }}">
                        {{ $timeslot }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>

            @foreach ($venues as $venue)
                <tr @class([
                    'opacity-50' => $venue->status === VenueStatus::HIDDEN,
                    'bg-amber-100 even:bg-amber-50' =>
                        $venue->tier === 1 || in_array($venue->id, $goldVenues),
                    'odd:bg-gray-100' => !(
                        $venue->tier === 1 || in_array($venue->id, $goldVenues)
                    ),
                ])>
                    <td class="pl-2 text-center w-28">
                        <div class="flex items-center justify-center h-12 {{ $showVenueModals ? 'cursor-pointer' : 'cursor-default' }}"
                            @if ($showVenueModals) x-on:click="$dispatch('open-modal', { id: 'venue-details-{{ $venue->id }}' })" @endif>
                            @if ($venue->logo_path)
                                <img src="{{ $venue->logo }}" loading="lazy" alt="{{ $venue->name }}"
                                    class="object-contain max-h-[48px] max-w-[112px]">
                            @else
                                <span class="text-xs font-semibold text-center uppercase line-clamp-2">
                                    {{ $venue->name }}
                                    @if ($venue->status === VenueStatus::HIDDEN)
                                        <span class="block text-[10px] text-gray-500">(Hidden)</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                    </td>

                    @foreach ($venue->schedules as $index => $schedule)
                        <td
                            class="p-1 pr-2 {{ $loop->first ? 'hidden sm:table-cell' : '' }} {{ $loop->last ? 'hidden sm:table-cell' : '' }}">
                            <button
                                @if (
                                    $schedule->is_bookable &&
                                        !$schedule->is_within_buffer &&
                                        ($venue->status === VenueStatus::ACTIVE ||
                                            (auth()->user()?->hasRole('super_admin') && $venue->status === VenueStatus::HIDDEN))) wire:click="createBooking({{ $schedule->id }}, '{{ $schedule->booking_date->format('Y-m-d') }}')"
                            @elseif ($venue->status === VenueStatus::UPCOMING)
                                x-on:click="$dispatch('open-modal', { id: 'pending-venue-{{ $venue->id }}' })" @endif
                                @class([
                                    'text-sm font-semibold rounded p-1 w-full mx-1 flex flex-col gap-y-[1px] justify-center items-center h-12',
                                    'bg-green-600 text-white cursor-pointer hover:bg-green-500' =>
                                        !$schedule->is_within_buffer &&
                                        $schedule->prime_time &&
                                        $schedule->is_bookable &&
                                        ($venue->status === VenueStatus::ACTIVE ||
                                            (auth()->user()?->hasRole('super_admin') &&
                                                $venue->status === VenueStatus::HIDDEN)),
                                    'bg-info-400 text-white cursor-pointer hover:bg-info-500' =>
                                        !$schedule->is_within_buffer &&
                                        !$schedule->prime_time &&
                                        $schedule->is_bookable &&
                                        ($venue->status === VenueStatus::ACTIVE ||
                                            (auth()->user()?->hasRole('super_admin') &&
                                                $venue->status === VenueStatus::HIDDEN)),
                                    'bg-[#E29B46] text-white cursor-pointer hover:bg-orange-500' =>
                                        !$schedule->is_within_buffer &&
                                        $schedule->has_low_inventory &&
                                        $schedule->is_bookable &&
                                        ($venue->status === VenueStatus::ACTIVE ||
                                            (auth()->user()?->hasRole('super_admin') &&
                                                $venue->status === VenueStatus::HIDDEN)),
                                    'bg-gray-200 text-gray-500 border-none cursor-not-allowed' =>
                                        (!$schedule->is_bookable && $venue->status !== VenueStatus::PENDING) ||
                                        $schedule->is_within_buffer,
                                    'bg-gray-200 text-gray-500 hover:bg-gray-200 cursor-pointer' =>
                                        $venue->status === VenueStatus::PENDING,
                                ])>
                                @if ($venue->status === VenueStatus::PENDING)
                                    <p>
                                        <span class="text-sm font-semibold uppercase">Soon</span>
                                    </p>
                                @elseif ($schedule->is_within_buffer)
                                    <p class="text-xs text-gray-500">
                                        N/A
                                    </p>
                                @elseif ($schedule->is_bookable && $schedule->prime_time)
                                    <p class="text-base font-bold">
                                        {{ moneyWithoutCents($schedule->fee($data['guest_count']), $currency) }}
                                    </p>
                                    @if ($schedule->has_low_inventory)
                                        <p class="-mt-1 text-xs font-semibold text-white">
                                            {{ $schedule->remaining_tables === 1 ? 'Last Table' : 'Last Tables' }}
                                        </p>
                                    @endif
                                    @if ($schedule->no_wait)
                                        <p class="-mt-1 text-xs font-semibold text-white">
                                            No Wait
                                        </p>
                                    @endif
                                    @if ($venue->is_omakase)
                                        <p class="-mt-1 text-xs font-semibold text-white">
                                            Omakase
                                        </p>
                                    @endif
                                @elseif($schedule->is_bookable && !$schedule->prime_time)
                                    <p class="text-xs uppercase text-nowrap sm:text-base">No Fee</p>
                                    @if ($schedule->has_low_inventory)
                                        <p class="-mt-1 text-xs font-semibold text-white">
                                            {{ $schedule->remaining_tables === 1 ? 'Last Table' : 'Last Tables' }}
                                        </p>
                                    @endif
                                    @if ($schedule->no_wait)
                                        <p class="-mt-1 text-xs font-semibold text-white">
                                            No Wait
                                        </p>
                                    @endif
                                    @if ($venue->is_omakase)
                                        <p class="-mt-1 text-xs font-semibold text-white">
                                            Omakase
                                        </p>
                                    @endif
                                @else
                                    <p class="text-xs uppercase text-nowrap">
                                        @if (!$schedule->is_bookable)
                                            @if ($schedule->is_available && $schedule->remaining_tables === 0)
                                                Sold Out
                                            @else
                                                Closed
                                            @endif
                                        @endif
                                    </p>
                                @endif
                            </button>
                        </td>
                    @endforeach
                </tr>

                {{-- Add spacing between tiers --}}
                @if (isset($venues[$loop->index + 1]))
                    @php
                        $currentIsGold = $venue->tier === 1 || in_array($venue->id, $goldVenues);
                        $currentIsSilver = $venue->tier === 2 || in_array($venue->id, $silverVenues);

                        $nextVenue = $venues[$loop->index + 1];
                        $nextIsGold = $nextVenue->tier === 1 || in_array($nextVenue->id, $goldVenues);
                        $nextIsSilver = $nextVenue->tier === 2 || in_array($nextVenue->id, $silverVenues);
                    @endphp

                    {{-- Add proper tier separator when transitioning from Gold to non-Gold --}}
                    @if ($currentIsGold && !$nextIsGold)
                        <tr class="bg-white" aria-hidden="true">
                            <td colspan="{{ count($timeslotHeaders) + 1 }}" class="relative">
                                <div class="h-2"></div>
                                <div class="absolute inset-x-0 bottom-0 border-b-2 border-gray-200"></div>
                            </td>
                        </tr>
                        <tr aria-hidden="true">
                            <td colspan="{{ count($timeslotHeaders) + 1 }}" class="h-2"></td>
                        </tr>
                        {{-- Add proper tier separator when transitioning from Silver to non-Silver (and non-Gold) --}}
                    @elseif ($currentIsSilver && !$nextIsSilver && !$nextIsGold)
                        <tr class="bg-white" aria-hidden="true">
                            <td colspan="{{ count($timeslotHeaders) + 1 }}" class="relative">
                                <div class="h-2"></div>
                                <div class="absolute inset-x-0 bottom-0 border-b-2 border-gray-200"></div>
                            </td>
                        </tr>
                        <tr aria-hidden="true">
                            <td colspan="{{ count($timeslotHeaders) + 1 }}" class="h-2"></td>
                        </tr>
                    @endif
                @endif
            @endforeach
        </tbody>
    </table>
</div>

@foreach ($venues as $venue)
    @if ($venue->status === VenueStatus::PENDING)
        <x-venue.pending-modal :venue="$venue" />
    @endif

    @if ($showVenueModals)
        {{-- Venue Details Modal --}}
        <x-filament::modal id="venue-details-{{ $venue->id }}" width="md" :close-button="false">
            {{-- Header with logo and address --}}
            <div class="flex items-start justify-between">
                <div class="flex-shrink-0 w-1/2">
                    @if ($venue->logo_path)
                        <img src="{{ $venue->logo }}" alt="{{ $venue->name }}" class="object-contain h-12">
                    @else
                        <span class="text-lg font-bold">{{ $venue->name }}</span>
                    @endif
                </div>
                <div class="w-1/2 pr-8 text-right">
                    <div class="text-xs text-right text-gray-700">
                        {{ $venue->address ?? '123 Main Street' }}
                    </div>
                    <div class="text-xs text-right text-gray-600 whitespace-nowrap">
                        {{ $venue->getFormattedLocation() }}
                    </div>
                    <div class="text-xs text-right text-indigo-500">
                        @if ($venue->cuisines && is_array($venue->cuisines) && count($venue->cuisines) > 0)
                            @php
                                $cuisineNames = collect($venue->cuisines)
                                    ->map(function ($cuisineId) {
                                        $cuisine = Cuisine::findById($cuisineId);
                                        return $cuisine ? $cuisine->name : null;
                                    })
                                    ->filter()
                                    ->join(', ');
                            @endphp
                            {{ $cuisineNames }}
                        @else
                            Cuisine information unavailable
                        @endif
                    </div>
                </div>
            </div>

            {{-- Close button in top right corner --}}
            <div class="absolute z-10 top-4 right-4">
                <button x-on:click="$dispatch('close-modal', { id: 'venue-details-{{ $venue->id }}' })"
                    class="text-gray-700 hover:text-gray-900 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Photo section - simplified carousel --}}
            <div class="mt-0">
                <div class="relative flex items-center justify-center overflow-hidden bg-gray-200 rounded-lg h-36">
                    @if (isset($venue->photo_path))
                        <img src="{{ $venue->photo_path }}" alt="{{ $venue->name }}"
                            class="object-cover w-full h-full">
                    @else
                        <span class="text-gray-500">Venue Photo</span>
                    @endif
                </div>
            </div>

            {{-- Description --}}
            <div class="mt-0">
                <p class="text-sm text-gray-700">
                    {{ $venue->clean_description ?: ($venue->description ?: 'This is a placeholder description for the venue. More details about the venue, cuisine, atmosphere, and special features would appear here. This is a placeholder description for the venue. More details about the venue, cuisine, atmosphere, and special features would appear here. This is a placeholder description for the venue. More details about the venue, cuisine, atmosphere, and special features would appear here.') }}
                </p>
            </div>

            {{-- Buttons Row --}}
            <div class="flex mt-1 space-x-2">
                {{-- Book Now Button --}}
                @php
                    // Get applicable schedule
                    $bookableSchedule = null;
                    $scheduleTime = '';

                    if (isset($venue->schedules) && count($venue->schedules) > 0) {
                        foreach ($venue->schedules as $schedule) {
                            if ($schedule->is_bookable && !$schedule->is_within_buffer) {
                                $bookableSchedule = $schedule;

                                // Extract display time (assuming start_time is available)
                                if (isset($schedule->start_time)) {
                                    $timeObj = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->start_time);
                                    $scheduleTime = $timeObj->format('g:i A');
                                }

                                break;
                            }
                        }
                    }

                    $isPrimeTime = $bookableSchedule ? $bookableSchedule->prime_time : false;
                    $hasLowInventory = $bookableSchedule ? $bookableSchedule->has_low_inventory : false;
                    $isBookable = $bookableSchedule ? true : false;

                    $buttonClass = $hasLowInventory
                        ? 'bg-[#E29B46] hover:bg-orange-500'
                        : ($isPrimeTime
                            ? 'bg-green-600 hover:bg-green-500'
                            : 'bg-info-400 hover:bg-info-500');
                @endphp

                <div class="w-1/2">
                    @if ($isBookable)
                        <button
                            wire:click="createBooking({{ $bookableSchedule->id }}, '{{ $bookableSchedule->booking_date->format('Y-m-d') }}')"
                            class="w-full py-2 px-4 {{ $buttonClass }} text-white text-xs font-semibold rounded-md focus:outline-none">
                            Book {{ $scheduleTime }}
                        </button>
                    @else
                        <button
                            class="w-full px-4 py-2 text-xs font-semibold text-gray-500 bg-gray-200 rounded-md cursor-not-allowed focus:outline-none">
                            No Available Times
                        </button>
                    @endif
                </div>

                {{-- Return to Calendar Button --}}
                <div class="w-1/2">
                    <button x-on:click="$dispatch('close-modal', { id: 'venue-details-{{ $venue->id }}' })"
                        class="w-full px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none">
                        Return To Calendar
                    </button>
                </div>
            </div>
        </x-filament::modal>
    @endif
@endforeach

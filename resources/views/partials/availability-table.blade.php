@php
    use App\Enums\VenueStatus;
    use App\Models\Region;
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
                <tr class="odd:bg-gray-100">
                    <td class="pl-2 text-center w-28">
                        <div class="flex items-center justify-center h-12">
                            @if ($venue->logo_path)
                                <img src="{{ $venue->logo }}" loading="lazy" alt="{{ $venue->name }}"
                                    class="object-contain max-h-[48px] max-w-[112px]">
                            @else
                                <span class="text-xs font-semibold text-center uppercase line-clamp-2">
                                    {{ $venue->name }}
                                </span>
                            @endif
                        </div>
                    </td>

                    @foreach ($venue->schedules as $index => $schedule)
                        <td
                            class="p-1 pr-2 {{ $loop->first ? 'hidden sm:table-cell' : '' }} {{ $loop->last ? 'hidden sm:table-cell' : '' }}">
                            <button
                                @if ($schedule->is_bookable && $venue->status === VenueStatus::ACTIVE) wire:click="createBooking({{ $schedule->id }}, '{{ $schedule->booking_date->format('Y-m-d') }}')" @else x-on:click="$dispatch('open-modal', { id: 'pending-venue-{{ $venue->id }}' })" @endif
                                @class([
                                    'text-sm font-semibold rounded p-1 w-full mx-1 flex flex-col gap-y-[1px] justify-center items-center h-12',
                                    'bg-green-600 text-white cursor-pointer hover:bg-green-500' =>
                                        $schedule->prime_time &&
                                        $schedule->is_bookable &&
                                        $venue->status === VenueStatus::ACTIVE,
                                    'bg-info-400 text-white cursor-pointer hover:bg-info-500' =>
                                        !$schedule->prime_time &&
                                        $schedule->is_bookable &&
                                        $venue->status === VenueStatus::ACTIVE,
                                    'bg-[#E29B46] text-white cursor-pointer hover:bg-orange-500' =>
                                        $schedule->has_low_inventory &&
                                        $schedule->is_bookable &&
                                        $venue->status === VenueStatus::ACTIVE,
                                    'bg-gray-200 text-gray-500 border-none cursor-not-allowed' =>
                                        !$schedule->is_bookable && $venue->status !== VenueStatus::PENDING,
                                    'bg-gray-200 text-gray-500 hover:bg-gray-200 cursor-pointer' =>
                                        $venue->status === VenueStatus::PENDING,
                                ])>
                                @if ($venue->status === VenueStatus::PENDING)
                                    <p>
                                        <span class="text-sm font-semibold uppercase">Soon</span>
                                    </p>
                                @elseif ($schedule->is_bookable && $schedule->prime_time)
                                    <p class="text-base font-bold">
                                        {{ moneyWithoutCents($schedule->fee($data['guest_count']), $currency) }}
                                    </p>
                                    @if ($schedule->has_low_inventory)
                                        <p class="-mt-1 text-xs font-light">
                                            Last Tables
                                        </p>
                                    @endif
                                @elseif($schedule->is_bookable && !$schedule->prime_time)
                                    <p class="text-xs uppercase text-nowrap sm:text-base">No Fee</p>
                                    @if ($schedule->has_low_inventory)
                                        <p class="-mt-1 text-xs font-light">
                                            Last Tables
                                        </p>
                                    @endif
                                @else
                                    <p class="text-xs uppercase text-nowrap">
                                        @if (!$schedule->is_bookable)
                                            @if (isPastCutoffTime($venue))
                                                N/A
                                            @elseif ($schedule->is_available && $schedule->remaining_tables === 0)
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
            @endforeach
        </tbody>
    </table>
</div>

@foreach ($venues as $venue)
    @if ($venue->status === VenueStatus::PENDING)
        <x-filament::modal id="pending-venue-{{ $venue->id }}" width="md">
            <x-slot name="heading">
                {{ $venue->name }} Coming Soon
            </x-slot>

            <p class="mt-2">
                Our team is currently working with {{ $venue->name }} to complete onboarding.
                We anticipate {{ $venue->name }} to be available on PRIMA soon.
            </p>

            <p class="mt-4">
                If you require an urgent reservation to this venue, please write to us at
                <a href="mailto:prima@primavip.co" class="text-primary-600 hover:text-primary-500">prima@primavip.co</a>
                and our team will assist.
            </p>

            <x-slot name="footerActions">
                <x-filament::button x-on:click="$dispatch('close-modal', { id: 'pending-venue-{{ $venue->id }}' })">
                    Close
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    @endif
@endforeach

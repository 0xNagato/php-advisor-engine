@php
    use App\Enums\VenueStatus;
    use Carbon\Carbon;

    $stickyHeaderTopPosition = isPrimaApp() ? 'top-0' : 'top-16';
@endphp
<x-filament-panels::page>
    {{ $this->form }}

    <div>
        @if (filled($venues))
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
                                    @if ($venue->logo_path)
                                        <img src="{{ $venue->logo }}" loading="lazy" alt="{{ $venue->name }}"
                                            class="object-cover max-h-[48px]">
                                    @else
                                        <span class="text-sm font-semibold line-clamp-2">
                                            {{ $venue->name }}
                                        </span>
                                    @endif
                                </td>

                                @foreach ($venue->schedules as $index => $schedule)
                                    <td
                                        class="p-1 pr-2 {{ $loop->first ? 'hidden sm:table-cell' : '' }} {{ $loop->last ? 'hidden sm:table-cell' : '' }}">
                                        <button
                                            @if ($schedule->is_bookable && $venue->status === VenueStatus::ACTIVE) wire:click="createBooking({{ $schedule->id }}, '{{ $schedule->booking_date->format('Y-m-d') }}')" @endif
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
                                                'bg-gray-200 text-gray-500 border-none' => !$schedule->is_bookable,
                                                'bg-gray-200 text-gray-500 hover:bg-gray-200 cursor-not-allowed' =>
                                                    $venue->status === VenueStatus::PENDING,
                                            ])>
                                            @if ($venue->status === VenueStatus::PENDING)
                                                <p>
                                                    <span class="text-xs font-semibold">Not Yet</span>
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
                                                <p class="text-base text-nowrap">N/A</p>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>

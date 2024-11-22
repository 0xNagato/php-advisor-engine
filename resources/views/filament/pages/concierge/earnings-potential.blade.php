@php
    use App\Enums\VenueStatus;
    $stickyHeaderTopPosition = isPrimaApp() ? 'top-0' : 'top-16';
@endphp
<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}
    </form>

    @if ($venues)

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
                                    @if ($venue->logo)
                                        <img src="{{ $venue->logo }}" loading="lazy" alt="{{ $venue->name }}"
                                            class="object-contain max-h-[48px] max-w-[112px]">
                                    @else
                                        <span class="text-sm font-semibold text-center line-clamp-2">
                                            {{ $venue->name }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            @foreach ($venue->schedules as $index => $schedule)
                                <td
                                    class="p-1 pr-2 {{ $loop->first ? 'hidden sm:table-cell' : '' }} {{ $loop->last ? 'hidden sm:table-cell' : '' }}">
                                    <div @class([
                                        'text-sm font-semibold rounded p-1 w-full mx-1 flex flex-col gap-y-[1px] justify-center items-center h-12',
                                        'bg-green-600 text-white' =>
                                            $schedule->prime_time &&
                                            $schedule->is_bookable &&
                                            $venue->status === VenueStatus::ACTIVE,
                                        'bg-info-400 text-white' =>
                                            !$schedule->prime_time &&
                                            $schedule->is_bookable &&
                                            $venue->status === VenueStatus::ACTIVE,
                                        'bg-[#E29B46] text-white' =>
                                            $schedule->has_low_inventory &&
                                            $schedule->is_bookable &&
                                            $venue->status === VenueStatus::ACTIVE,
                                        'bg-gray-200 text-gray-500 border-none' => !$schedule->is_bookable,
                                        'bg-gray-200 text-gray-500' => $venue->status === VenueStatus::PENDING,
                                    ])>
                                        @if ($venue->status === VenueStatus::PENDING)
                                            <p>
                                                <span class="text-xs font-semibold">Soon</span>
                                            </p>
                                        @elseif ($schedule->is_bookable)
                                            @php
                                                $isPrime = $schedule->prime_time;
                                                $earnings = $this->conciergePayout($venue, $isPrime);
                                            @endphp
                                            <p class="text-base font-bold">
                                                @money($earnings, $this->currency)
                                            </p>
                                        @else
                                            <p class="text-base text-nowrap">N/A</p>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>

@php use Carbon\Carbon; @endphp
<x-filament-panels::page>
    {{ $this->form }}

    <div>
        @if (!empty($restaurants))
            <div
                class="grid grid-cols-[100px_repeat(3,_1fr)] items-center bg-white sticky top-28 shadow border-t sm:border-none sm:mt-0 -mt-4 sm:mx-0 -mx-4">
                <div></div>
                <div class="col-span-3 grid grid-cols-3">
                    @foreach($restaurants[0]->schedules as $schedule)
                        <div class="text-center text-sm font-semibold p-2">
                            {{ Carbon::createFromFormat('H:i:s', $schedule->start_time)->format('g:i A') }}
                        </div>
                    @endforeach
                </div>
            </div>

            <div
                class="grid grid-cols-[100px_repeat(3,_1fr)] auto-rows-fr p-2 divide-y pt-0 items-center bg-white rounded-b shadow-sm sm:mx-0 -mx-4"
            >
                @foreach($restaurants as $restaurant)
                    <div
                        class="flex h-12 items-center truncate text-base font-semibold text-wrap ">
                        @if($restaurant->logo)
                            <img src="{{ $restaurant->logo }}"
                                 alt="{{ $restaurant->restaurant_name }}"
                                 class="h-12 w-12 object-cover">
                        @else
                            {{ $restaurant->restaurant_name }}
                        @endif
                    </div>

                    @foreach($restaurant->schedules as $schedule)
                        <div class="flex h-12 items-center justify-around border-l">
                            <button
                                @if ($schedule->is_bookable) wire:click="createBooking({{ $schedule->id }}, '{{ $schedule->booking_date->format('Y-m-d') }}')" @endif
                                @class([
                                    'text-sm font-semibold rounded p-2 w-full mx-1',
                                    'bg-green-600 text-white cursor-pointer hover:bg-green-500' => $schedule->is_bookable,
                                    'bg-gray-200 text-gray-400 border-none' => !$schedule->is_bookable,
                                ])
                            >
                                <div>
                                    @if($schedule->is_bookable && $schedule->prime_time)
                                        {{ moneyWithoutCents($schedule->fee($data['guest_count'])) }}
                                    @elseif($schedule->is_bookable && !$schedule->prime_time)
                                        <span class="text-xs text-nowrap">No Fee</span>
                                    @else
                                        <span class="text-xs text-nowrap">Sold Out</span>
                                    @endif
                                </div>

                            </button>
                        </div>
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>

@php use Carbon\Carbon; @endphp
<x-filament-panels::page>
    {{ $this->form }}


    <div>
        @foreach($resraurants as $restaurant)
            <div class="grid grid-cols-3 gap-2 items-center">
                @if ($loop->first)
                    <div></div>
                    <div class="col-span-2 grid grid-cols-3">
                        @foreach($restaurant['schedules'] as $schedule)
                            <div class="text-center text-xs font-semibold">
                                {{ Carbon::createFromFormat('H:i:s', $schedule->start_time)->format('g:i A') }}
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="col-span-1 text-xs font-semibold">{{ $restaurant['restaurant']->restaurant_name }}</div>

                <div class="col-span-2 grid grid-cols-3 gap-4 mb-3">

                    @foreach($restaurant['schedules'] as $schedule)
                        <button @if ($schedule->is_bookable) wire:click="createBooking({{ $schedule->id }})" @endif
                            @class([
                                'flex flex-col gap-1 items-center p-3 text-sm font-semibold leading-none rounded-xl justify-center',
                                'outline outline-2 outline-offset-2 outline-green-600' => $schedule->start_time === $this->data['reservation_time'],
                                'outline outline-2 outline-offset-2 outline-gray-100' => $schedule->start_time === $this->data['reservation_time'] && !$schedule->is_bookable,
                                'outline outline-2 outline-offset-2 outline-indigo-600' => $schedule->start_time === $this->data['reservation_time'] && $schedule->prime_time,
                                'bg-green-600 text-white cursor-pointer hover:bg-green-500' => $schedule->is_bookable,
                                'bg-gray-100 text-gray-400 border-none' => !$schedule->is_bookable,
                            ])
                        >
                            <div class="text-sm">
                                {{ $schedule->is_bookable ? money($schedule->fee($data['guest_count'])) : 'Sold Out' }}
                            </div>

                            @if($schedule->is_bookable && $schedule->remaining_tables <= 5)

                                <div
                                    class="bg-red-500 px-1 py-1 shadow-xl text-white top-0 right-4 text-[10px] text-nowrap rounded">
                                    Last Tables
                                </div>

                            @endif

                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>

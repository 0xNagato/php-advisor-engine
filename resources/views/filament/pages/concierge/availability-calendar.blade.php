@php use Carbon\Carbon; @endphp
<x-filament-panels::page>
    {{ $this->form }}

    <div>
        @if (!empty($restaurants))
            <div class="grid grid-cols-[100px_repeat(3,_1fr)] gap-2 p-2 items-center bg-white sticky top-28">
                <div></div>
                <div class="col-span-3 grid grid-cols-3">
                    @foreach(current($restaurants)['schedules'] as $schedule)
                        <div class="text-center text-xs font-semibold">
                            {{ Carbon::createFromFormat('H:i:s', $schedule->start_time)->format('g:i A') }}
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-[100px_repeat(3,_1fr)] auto-rows-fr divide-y-2 p-2 pt-0 items-center bg-white">
                @foreach($restaurants as $restaurant)
                    <div class="flex items-center h-12 text-base font-semibold text-wrap truncate">
                        @if($restaurant['restaurant']->logo)
                            <img src="{{ $restaurant['restaurant']->logo }}"
                                alt="{{ $restaurant['restaurant']->restaurant_name }}"
                                class="w-12 h-12 object-cover">
                        @else
                            {{ $restaurant['restaurant']->restaurant_name }}
                        @endif
                    </div>

                    @foreach($restaurant['schedules'] as $schedule)
                        <div class="flex justify-around items-center h-12 border-l-2">
                            <button
                                @if ($schedule->is_bookable) wire:click="createBooking({{ $schedule->id }}, '{{ $schedule->booking_date->format('Y-m-d') }}')" @endif
                                @class([
                                    'text-sm font-semibold rounded-xl p-1', // 'flex flex-col gap-1 items-center p-1 text-sm font-semibold leading-none rounded-xl justify-center',
                                    // 'outline outline-2 outline-offset-2 outline-green-600' => $schedule->start_time === $this->data['reservation_time'],
                                    // 'outline outline-2 outline-offset-2 outline-gray-100' => $schedule->start_time === $this->data['reservation_time'] && !$schedule->is_bookable,
                                    'bg-green-600 text-white cursor-pointer hover:bg-green-500' => $schedule->is_bookable,
                                    'bg-gray-100 text-gray-400 border-none' => !$schedule->is_bookable,
                                ])
                            >
                                <div>
                                    @if($schedule->is_bookable && $schedule->prime_time)
                                        {{ money($schedule->fee($data['guest_count'])) }}
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

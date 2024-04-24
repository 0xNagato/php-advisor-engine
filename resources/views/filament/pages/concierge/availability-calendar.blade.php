@php use Carbon\Carbon; @endphp
<x-filament-panels::page>
    {{ $this->form }}

    <div>
        @foreach($restaurants as $restaurant)
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
                <div class="col-span-1 text-base font-semibold">{{ $restaurant['restaurant']->restaurant_name }}</div>

                <div class="col-span-2 grid grid-cols-3 gap-4 mb-3">

                    @foreach($restaurant['schedules'] as $schedule)
                        <button
                            @if ($schedule->is_bookable) wire:click="createBooking({{ $schedule->id }}, '{{ $schedule->booking_date->format('Y-m-d') }}')" @endif
                            @class([
                                'flex flex-col gap-1 items-center p-3 text-sm font-semibold leading-none rounded-xl justify-center',
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
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
